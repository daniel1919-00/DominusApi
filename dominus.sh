#!/bin/sh
DIR=".cli"

if ! command -v git > /dev/null 2>&1; then
    echo "Git is not installed. Exiting.";
    exit 1;
fi

if [ ! -d "$DIR" ]; then
    echo "Cloning DominusCli repository...";
    if ! git clone https://github.com/daniel1919-00/DominusCli "$DIR"; then
        echo "Git clone failed. Exiting.";
        exit 1;
    fi
fi

cd "$DIR" || exit;
bash dominus.sh;