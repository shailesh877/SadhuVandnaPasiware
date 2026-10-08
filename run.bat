@echo off
set JAVA_HOME=d:\bangosambadApp\jdk17\jdk-17.0.14+7
set PATH=%JAVA_HOME%\bin;%PATH%;C:\Users\Pasiware\AppData\Local\Android\Sdk\platform-tools;C:\Users\Pasiware\AppData\Local\Android\Sdk\emulator;C:\Users\Pasiware\AppData\Local\Android\Sdk\tools\bin
echo Starting Simple Build...

echo Step 1: SDK Path Fix...
if not exist android mkdir android
(echo sdk.dir=C\:\\Users\\Pasiware\\AppData\\Local\\Android\\Sdk)> android\local.properties

echo Step 2: Syncing...
call npm install

echo Step 3: Running App...
npx expo run:android

pause
