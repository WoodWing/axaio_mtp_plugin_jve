"""
Listens for SCE messages and transforms them to real multicasts

Usage: %(PROGRAM)s [-a addr] [-i port] [-g group] [-o port] [-t ttl] [-n interface] [-b port]

        -a 
        --in_address
                Input IP address used for listing for SCE server.
                Default value: %(MC_INPUT_ADDRESS)s
 
        -i
        --in_port
                Input IP port used for listening for SCE server.
                Default value: %(MC_INPUT_PORT)s
                
        -g
        --out_group
                Ouput IP group/address used for multicasting to SCE clients.
                Default value: %(MC_OUTPUT_GROUP)s
                
        -o
        --out_port
                Ouput IP port used for multicasting to SCE clients.
                Default value: %(MC_OUTPUT_PORT)s
                
        -t
        --out_ttl
                Ouput TTL (time-to-live) used for multicasting to SCE clients.
                Default value: %(MC_OUTPUT_TTL)s
                
        -n
        --out_if
                Ouput network interface address used for multicasting to SCE clients.
                Default value: %(MC_OUTPUT_IF)s
                
        -b
        --bc_port
                Output IP port used for answering on body check requests (to see if mediator is still alive).
                Default value: %(BC_PORT)s
                
        -h
        --help
                Shows this help message.
"""

# incomming connection (defaults) from SCE server
MC_INPUT_ADDRESS = '127.0.0.1' 
MC_INPUT_PORT = 8094

# outgoing multicast connection (defaults) to SCE clients
MC_OUTPUT_GROUP = '224.0.252.1' # multicast group address
MC_OUTPUT_PORT = 8093 # multicast port
MC_OUTPUT_TTL = 32 # time to live for multicasts
MC_OUTPUT_IF = '127.0.0.1' # outgoing network interface address
BC_PORT = 27123 # IP port used to respond on body check requests

import sys 
import time 
import struct 
import string
import getopt
from socket import * 

PROGRAM = sys.argv[0]

def usage(code, msg=''):
        print __doc__ % globals()
        if msg:
                print msg
        sys.exit(code)

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
        #s.setsockopt(IPPROTO_IP, IP_MULTICAST_LOOP, ttl)
        s.setsockopt(IPPROTO_IP, IP_MULTICAST_TTL, ttl)
        
# Main program 
def main():

        in_adr = MC_INPUT_ADDRESS
        in_port = MC_INPUT_PORT
        out_group = MC_OUTPUT_GROUP
        out_port = MC_OUTPUT_PORT
        out_ttl = MC_OUTPUT_TTL
        out_if = MC_OUTPUT_IF
        bc_port = BC_PORT
        help=0
        
        try:
                opts, args = getopt.getopt(
                        sys.argv[1:],
                        'p:a:i:g:o:t:n:b:h',
                        ['in_adr=', 'in_port=', 'out_group=', 'out_port=', 'out_ttl=', 'out_if=', 'bc_port=', 'help'])
        except getopt.error, msg:
                usage(1, msg)

        for opt, arg in opts:
                if opt in ('-a', '--in_adr'):
                        in_adr = arg
                elif opt in ('-i', '--in_port'):
                        in_port = string.atoi(arg)
                elif opt in ('-b', '--bc_port'):
                        bc_port = string.atoi(arg)
                elif opt in ('-g', '--out_group'):
                        out_group = arg
                elif opt in ('-o', '--out_port'):
                        out_port = string.atoi(arg)
                elif opt in ('-t', '--out_ttl'):
                        out_ttl= string.atoi(arg)
                elif opt in ('-n', '--out_if'):
                        out_if = arg
                elif opt in ('-h', '--help'):
                        help=1
        if help:
                usage(0)

        #print 'input:', in_adr, ':', in_port, '\noutput:', out_group, ':', out_port, 'ttl=', out_ttl, 'if=', out_if
        #sys.exit(1)

        main_transmitter( in_adr, in_port, out_group, out_port, out_ttl, out_if, bc_port )                 
                
# Tranmitter for multicasts
def main_transmitter( in_adr, in_port, out_group, out_port, out_ttl, out_if, bc_port ):
 
        # Open and initialize the socket for input
        s_in = open_input_socket(in_port) 
        
        s_out = socket(AF_INET, SOCK_DGRAM)
        setTTL(s_out,out_ttl) # time-to-live
        setIF(s_out,out_if) # outgoing interface
        
        print 'Started with following parameters:\ninput:', in_adr, ':', in_port, '\noutput:', out_group, ':', out_port, 'ttl=', out_ttl, 'if=', out_if, 'bc_port=', bc_port

        running = 1
        # Loop, printing any data we receive 
        while running: 
                data, sender = s_in.recvfrom(1500) 
                while data[-1:] == '\0': data = data[:-1] # Strip trailing \0's 
                print 'received: ', sender, ':', `data`

                if data == 'Mediator_Exit':
                        running = 0
                elif  data == 'Mediator_BodyCheck':
                     s_bc = socket(AF_INET, SOCK_DGRAM)
                     s_bc.setsockopt(SOL_SOCKET, SO_BROADCAST, 1)
                     s_bc.sendto('Multicast Mediator is alive!', ('<broadcast>', bc_port)) 
                     print 'send: Multicast Mediator is alive!'
                     s_bc.close()
                else:
                     s_out.sendto(data, (out_group, out_port)) 
                     print 'send: ', `data`


def open_input_socket(port): 

        # Create a socket 
        s = socket(AF_INET, SOCK_DGRAM) 

        # Allow multiple copies of this program on one machine 
        s.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1) 

        # Bind it to the port 
        s.bind(('', port)) 

        return s 


main() 
