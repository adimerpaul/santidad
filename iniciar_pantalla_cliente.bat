@echo off
:: Cierra instancias anteriores del perfil exclusivo de la pantalla del cliente para evitar duplicados
taskkill /IM chrome.exe /FI "WINDOWTITLE eq clienteDisplay" /F >nul 2>&1

:: Ruta a Google Chrome
set CHROME_PATH="C:\Program Files\Google\Chrome\Application\chrome.exe"
if not exist %CHROME_PATH% set CHROME_PATH="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
if not exist %CHROME_PATH% set CHROME_PATH="%LocalAppData%\Google\Chrome\Application\chrome.exe"

:: Abre Chrome en Modo Kiosco (Pantalla Completa automática en el segundo monitor)
:: --window-position=1920,0 asume que el segundo monitor está a la derecha del principal.
:: --user-data-dir crea un perfil de usuario temporal separado para que no interfiera con las sesiones de venta del vendedor en la pantalla principal.
start "" %CHROME_PATH% --app="http://localhost:8080/cliente-display" --kiosk --window-position=1920,0 --user-data-dir="%LocalAppData%\Google\Chrome\User Data DisplayCliente"

echo Pantalla de cliente iniciada en modo Kiosco.
