#!/bin/sh
sudo -u wwfiletransfer hscp -B %username%@%host%:%remotefile% %localfile% 2>&1 1> /dev/null