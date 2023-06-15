@echo off

python3 --version > nul 2>&1
if %errorlevel% equ 0 (
    set PYTHON_COMMAND=python3
) else (
    set PYTHON_COMMAND=python
)

IF exist .cli\ (
    cd .cli
    git fetch
    git reset --hard HEAD
    git clean -fxd
    git pull
    %PYTHON_COMMAND% -m pip install -q -r requirements.txt
    %PYTHON_COMMAND% .\dominus_cli\run.py
) ELSE (
    git clone https://github.com/daniel1919-00/DominusCli .cli
    %PYTHON_COMMAND% -m pip install -q -r .cli\requirements.txt
    %PYTHON_COMMAND% .cli\dominus_cli\run.py
)