#!/bin/sh
sudo -u wwfiletransfer hscp -B %localfile% %username%@%host%:%remotefile% 2>&1 1> /dev/null