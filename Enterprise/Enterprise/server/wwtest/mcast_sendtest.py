# Multicast sender

MYPORT = 8093 # multicast port
MYGROUP = '224.0.252.1' # multicast group (or use '<broadcast>' for broadcasting)
MY_TTL = 32 # time to live for multicasts
MY_IF = "127.0.0.1" # network interface

import time 
import struct 
from socket import * 

# Main program 
def main():
        mysender() 


def setIF(s, addr):
        i = inet_aton(addr)
        s.setsockopt(IPPROTO_IP, IP_MULTICAST_IF, i)
        return 1

def getTTL(s):
        #ttl = s.getsockopt(IPPROTO_IP, IP_MULTICAST_TTL)
        #return struct.unpack("B", ttl)[0]
        return s.getsockopt(IPPROTO_IP, IP_MULTICAST_TTL)
    
def setTTL(s, ttl):
        ttl = struct.pack("B", ttl) #[EKL@17/02/2006] Changed "i" option into "B" to let it work for Unix as well
        s.setsockopt(IPPROTO_IP, IP_MULTICAST_LOOP, ttl)
        s.setsockopt(IPPROTO_IP, IP_MULTICAST_TTL, ttl)

# Sender for mulicasts
def mysender(): 
        print 'Sender to test multicasting.\nPlease also start mcast_recvtest.py elsewhere on network.\nNow sending multicasts...\n'
        s = socket(AF_INET, SOCK_DGRAM) 
        if MYGROUP == '<broadcast>': 
                s.setsockopt(SOL_SOCKET, SO_BROADCAST, 1) 
                mygroup = '<broadcast>' 
        else: 
                mygroup = MYGROUP 
                setTTL(s, MY_TTL)	# Time-to-live
                setIF(s, MY_IF) # outgoing network interface
                #print getTTL(s)
        while 1: 
                data = time.strftime( "%Y-%m-%d@%H:%M:%S", time.localtime() )
                s.sendto(data, (mygroup, MYPORT)) 
                print 'send: ', `data`
                time.sleep(1) 

main() 
