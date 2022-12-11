#!/bin/bash
DIR=".cli"
if [ -d "$DIR" ]; then
  echo "Checking for updates..."
  cd "$DIR" || exit
  git fetch
  git reset --hard HEAD
  git clean -fxd
  git pull
  python3 -m pip install -q -r ./requirements.txt
  python3 ./dominus_cli/run.py
else
  git clone https://github.com/daniel1919-00/DominusCli "$DIR"
  python3 -m pip install -q -r "$DIR"/requirements.txt
  python3 "$DIR"/dominus_cli/run.py
fi