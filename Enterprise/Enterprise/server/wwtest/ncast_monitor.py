#! /usr/bin/env python
# Receives UDP multicast packets.
# Requires that your OS kernel supports IP multicast.
# This is built-in on SGI, still optional for most other vendors.
#
# Take over the EventPort and MulticastGroup settings from configserver.php (or specify as first argument)...
EventPort = 8093
# ...and choose ONE of the TWO (or specify as second argument):
MulticastGroup = '<broadcast>' # => broadcasting
#MulticastGroup = '224.0.252.1' # => multicasting

# Imports
import sys
import time
import struct
import string
from socket import *

# Main program
def main():
    #
    # Open and initialize the socket
    SERVERACTIONS = [
    '',
    'Logon',
    'Logoff',
    'CreateObject',
    'DeleteObject',
    'SaveObject',
    'SetObjectProperties',
    'SendTo',
    'LockObject',
    'UnlockObject',
    'CreateObjectRelation',
    'DeleteObjectRelation',
    'SendMessage',
    'UpdateObjectRelation',
    'DeadlineChanged',
    'DeleteMessage',
    'AddToQuery',
    'RemoveFromQuery',
    'ReLogOn',
    'RestoreVersion',
    'CreateObjectTarget',
    'DeleteObjectTarget',
    'UpdateObjectTarget',
    'RestoreObject',
    'IssueDossierReorderAtProduction',
    'IssueDossierReorderPublished',
    'PublishDossier',
    'UpdateDossier',
    'UnpublishDossier',
    'SetPublishInfoForDossier',
    'PublishIssue',
    'UpdateIssue',
    'UnpublishIssue',
    'SetPublishInfoForIssue',
    'CreateObjectLabels',
    'UpdateObjectLabels',
    'DeleteObjectLabels',
    'AddObjectLabels',
    'RemoveObjectLabels',
    'SetPropertiesForMultipleObjects']
    EVENTYTYPES = [ '', 'System', 'Client', 'User' ]
    if MulticastGroup == "<broadcast>" :
        print "Enterprise ncast_monitor.py started with listening for broadcasts on port=[%s]" % (EventPort)
    else :
        print "Enterprise ncast_monitor.py started with listening for multicasts on group=[%s] and port=[%s]" % (MulticastGroup, EventPort)
    s = open_ncast_socket(MulticastGroup, EventPort)
    #
    # Loop, printing any data we receive
    i = 0
    while 1:
        data, sender = s.recvfrom(1500)
        while data[-1:] == '\0': data = data[:-1] # Strip trailing \0's
        formatidx = ord(data[0])
        eventidx = ord(data[1])
        typeidx = ord(data[2])
        print '---------------------------------------------------------';
        print 'format : ', formatidx
        print 'event : ', `SERVERACTIONS[eventidx]`, '(id=%d)' % eventidx
        print 'type  : ', `EVENTYTYPES[typeidx]`, '(id=%d)' % typeidx
        #print 'received: ', sender, ':', `data`
        #print 'len:', len(data)
        offset = 4 # skip first four chars; format, event, type and reserved
        while offset < len(data):
            # parse key
            keysize = long(ord(data[offset]))
            keysize = (keysize << 8) | ord(data[offset+1])
            offset = offset + 2
            fieldkey = `data[offset:offset+keysize]`
            offset = offset + keysize
            # parse value
            if offset >= len(data) :
                break
            valsize = long(ord(data[offset]))
            valsize = (valsize << 8) | ord(data[offset+1])
            offset = offset + 2
            fieldval = `data[offset:offset+valsize]`
            offset = offset + valsize
            print "- %-20s: %s" % (fieldkey, fieldval)

# Open a UDP socket, bind it to a port and select a multicast group
# or open TCP broadcast socket and bind it to port
def open_ncast_socket(group, port):
    #
    # Create a socket
    s = socket(AF_INET, SOCK_DGRAM)
    #
    # Allow multiple copies of this program on one machine
    # (not strictly needed)
    s.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
    #
    broadcasting = group == "<broadcast>"
    if broadcasting: s.setsockopt(SOL_SOCKET, SO_BROADCAST, 1)
    # Bind it to the port
    s.bind(('', port))
    #
    # Look up multicast group address in name server
    # (doesn't hurt if it is already in ddd.ddd.ddd.ddd format)
    group = gethostbyname(group)
    #
    # Construct binary group address
    bytes = map(int, string.split(group, "."))
    grpaddr = int(0) 
    for byte in bytes: grpaddr = (grpaddr << 8) | byte
    # 
    # Construct struct mreq from grpaddr and ifaddr 
    ifaddr = INADDR_ANY 
    mreq = struct.pack('LL', htonl(grpaddr), htonl(ifaddr)) 
    # 
    # Add group membership 
    # EKL: does not work with 255.255.255.255
    if not broadcasting: s.setsockopt(IPPROTO_IP, IP_ADD_MEMBERSHIP, mreq)
    # 
    return s 

if __name__ == '__main__':
    # Allow specifying the EventPort and MulticastGroup as first and second argument
    if len(sys.argv) > 1:
        EventPort = int(sys.argv[1])
    if len(sys.argv) > 2:
        MulticastGroup = sys.argv[2]
    
    main() 
