#!/bin/sh

REDIRECTION_SERVICE="https://redirect.woodwing.com/v1/"
alias json_pretty_print="php -r 'echo json_encode(json_decode(fgets(STDIN)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).\"\n\";'"
