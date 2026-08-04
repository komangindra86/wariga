@echo off
setlocal EnableExtensions EnableDelayedExpansion

title Wariga Bali Santih - Android Launcher

set "PROJECT_DIR=%~dp0"
set "MOBILE_DIR=%PROJECT_DIR%mobile"

if not defined ANDROID_HOME set "ANDROID_HOME=%LOCALAPPDATA%\Android\Sdk"

set "EMULATOR=%ANDROID_HOME%\emulator\emulator.exe"
set "ADB=%ANDROID_HOME%\platform-tools\adb.exe"

if not exist "%MOBILE_DIR%\package.json" (
    echo [ERROR] Project mobile tidak ditemukan di:
    echo         %MOBILE_DIR%
    goto :failed
)

if not exist "%EMULATOR%" (
    echo [ERROR] Android Emulator tidak ditemukan di:
    echo         %EMULATOR%
    goto :failed
)

if not exist "%ADB%" (
    echo [ERROR] ADB tidak ditemukan di:
    echo         %ADB%
    goto :failed
)

where node >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js tidak ditemukan pada PATH.
    goto :failed
)

where npm >nul 2>&1
if errorlevel 1 (
    echo [ERROR] npm tidak ditemukan pada PATH.
    goto :failed
)

if not exist "%MOBILE_DIR%\node_modules" (
    echo [INFO] Dependency belum terpasang. Menjalankan npm install...
    pushd "%MOBILE_DIR%"
    call npm install
    if errorlevel 1 (
        popd
        echo [ERROR] npm install gagal.
        goto :failed
    )
    popd
)

set "AVD_NAME=%~1"
if not defined AVD_NAME (
    for /f "usebackq delims=" %%A in (`"%EMULATOR%" -list-avds`) do (
        if not defined AVD_NAME set "AVD_NAME=%%A"
    )
)

if not defined AVD_NAME (
    echo [ERROR] Tidak ada Android Virtual Device yang tersedia.
    echo         Buat AVD terlebih dahulu melalui Android Studio.
    goto :failed
)

"%ADB%" start-server >nul 2>&1

set "EMULATOR_RUNNING="
for /f "tokens=1,2" %%A in ('"%ADB%" devices 2^>nul') do (
    echo %%A | findstr /b "emulator-" >nul && if "%%B"=="device" set "EMULATOR_RUNNING=1"
)

if defined EMULATOR_RUNNING (
    echo [INFO] Emulator Android sudah aktif.
) else (
    echo [INFO] Menjalankan emulator: %AVD_NAME%
    start "Wariga Android Emulator" "%EMULATOR%" -avd "%AVD_NAME%"
)

echo [INFO] Menunggu Android selesai boot...
set /a WAIT_COUNT=0

:wait_for_boot
set "BOOT_COMPLETED="
for /f "usebackq delims=" %%B in (`"%ADB%" shell getprop sys.boot_completed 2^>nul`) do (
    set "BOOT_COMPLETED=%%B"
)

if "!BOOT_COMPLETED!"=="1" goto :android_ready

set /a WAIT_COUNT+=1
if !WAIT_COUNT! geq 120 (
    echo [ERROR] Emulator belum siap setelah 4 menit.
    goto :failed
)

timeout /t 2 /nobreak >nul
goto :wait_for_boot

:android_ready
echo [OK] Android siap.

netstat -ano | findstr ":8081 " | findstr "LISTENING" >nul
if errorlevel 1 (
    echo [INFO] Menjalankan Metro Bundler di jendela terpisah...
    start "Wariga Metro Bundler" /D "%MOBILE_DIR%" cmd /k "call npm start"
) else (
    echo [INFO] Metro Bundler sudah aktif pada port 8081.
)

echo [INFO] Menunggu Metro Bundler siap...
set /a METRO_WAIT_COUNT=0

:wait_for_metro
set "METRO_STATUS="
for /f "usebackq delims=" %%M in (`curl.exe -s --max-time 2 http://127.0.0.1:8081/status 2^>nul`) do (
    set "METRO_STATUS=%%M"
)

if "!METRO_STATUS!"=="packager-status:running" goto :metro_ready

set /a METRO_WAIT_COUNT+=1
if !METRO_WAIT_COUNT! geq 60 (
    echo [ERROR] Metro Bundler belum siap setelah 2 menit.
    echo         Periksa pesan pada jendela "Wariga Metro Bundler".
    goto :failed
)

timeout /t 2 /nobreak >nul
goto :wait_for_metro

:metro_ready
echo [OK] Metro Bundler siap.
"%ADB%" reverse tcp:8081 tcp:8081 >nul

echo [INFO] Memasang dan membuka Wariga Bali Santih...
pushd "%MOBILE_DIR%"
call npm run android:development -- --no-packager
set "APP_RESULT=%ERRORLEVEL%"
popd

if not "%APP_RESULT%"=="0" (
    echo [ERROR] Build atau pemasangan aplikasi gagal.
    goto :failed
)

echo.
echo [OK] Wariga Bali Santih sudah dijalankan pada emulator.
echo      Jendela ini boleh ditutup. Biarkan Metro Bundler tetap aktif.
echo.
pause
exit /b 0

:failed
echo.
echo Proses dihentikan. Periksa pesan error di atas.
pause
exit /b 1
