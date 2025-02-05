where git >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo Git is not installed. Exiting.
    exit /b 1
)

set DIR=.cli

if not exist "%DIR%" (
    echo Cloning DominusCli repository...
    git clone https://github.com/daniel1919-00/DominusCli "%DIR%"
    if %ERRORLEVEL% neq 0 (
        echo Git clone failed. Exiting.
        exit /b 1
    )
)

cd /d "%DIR%"
if %ERRORLEVEL% neq 0 (
    echo Failed to change directory to %DIR%. Exiting.
    exit /b 1
)

call dominus.bat