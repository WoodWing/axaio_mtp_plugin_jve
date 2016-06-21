# Multicast receiver test

MYPORT = 8093 # multicast port
MYGROUP = '224.0.252.1' # multicast group (or use '<broadcast>' for broadcasting)

#import sys 
import time 
import struct 
##import regsub 
from socket import * 

# Main program 
def main():
        myreceiver()                 
                
# Receiver for mulicasts
def myreceiver(): 
        print 'Receiver to test multicasting.\nPlease also start mcast_sendtest.py elsewhere on network.\nNow listening for multicasts...\n'
        # Open and initialize the socket 
        s = open_mcast_socket(MYGROUP, MYPORT) 
        # 
        # Loop, printing any data we receive 
        while 1: 
                data, sender = s.recvfrom(1500) 
                while data[-1:] == '\0': data = data[:-1] # Strip trailing \0's 
                print 'received: ', sender, ':', `data`


# Open a UDP socket, bind it to a port and select a multicast group 
def open_mcast_socket(group, port): 
        # Import modules used only here 
        import string 
        import struct 
        # 
        # Create a socket 
        s = socket(AF_INET, SOCK_DGRAM) 
        #s.setblocking(0)
        # 
        # Allow multiple copies of this program on one machine 
        # (not strictly needed) 
        s.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1) 
        # 
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
        mreq = struct.pack('ll', htonl(grpaddr), htonl(ifaddr)) 
        # 
        # Add group membership 
        # EKL: does not work with 255.255.255.255
        s.setsockopt(IPPROTO_IP, IP_ADD_MEMBERSHIP, mreq) 
        # 
        return s 


main() 
