@echo off
IF exist .cli\ (
    cd .cli 
    git fetch
    git reset --hard HEAD
    git pull
    python3 -m pip install -q -r requirements.txt
    python3 .\dominus_cli\run.py
) ELSE (
    git clone https://github.com/daniel1919-00/DominusCli .cli
    python3 -m pip install -q -r .cli\requirements.txt
    python3 .cli\dominus_cli\run.py
)