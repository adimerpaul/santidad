param(
    [string]$BaseUrl = "https://apimktdesa.baneco.com.bo/ApiGateway",
    [string]$AesKey = "40A318B299F245C2B697176723088629",
    [string]$PlainText = "1234",
    [string]$UserName = "26551010",
    [string]$PasswordPlain = "1234",
    [string]$AccountCredit = "1061602532"
)

$ErrorActionPreference = "Stop"

function Invoke-BanecoGet {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,
        [hashtable]$Query = @{}
    )

    $queryString = ($Query.Keys | ForEach-Object {
            "{0}={1}" -f [System.Uri]::EscapeDataString($_), [System.Uri]::EscapeDataString([string]$Query[$_])
        }) -join "&"

    $url = "$BaseUrl$Path"
    if ($queryString) {
        $url = "$url`?$queryString"
    }

    return Invoke-RestMethod -Method Get -Uri $url -ContentType "application/json"
}

function Invoke-BanecoPost {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,
        [Parameter(Mandatory = $true)]
        [object]$Body
    )

    $url = "$BaseUrl$Path"
    $jsonBody = $Body | ConvertTo-Json -Depth 5
    return Invoke-RestMethod -Method Post -Uri $url -ContentType "application/json" -Body $jsonBody
}

Write-Host "== Prueba 1: Encriptación =="
$cipherText = Invoke-BanecoGet -Path "/api/authentication/encrypt" -Query @{
    text   = $PlainText
    aesKey = $AesKey
}
Write-Host "Texto plano: $PlainText"
Write-Host "Texto cifrado: $cipherText"

Write-Host ""
Write-Host "== Prueba 2: Desencriptación =="
$decryptedText = Invoke-BanecoGet -Path "/api/authentication/decrypt" -Query @{
    text   = $cipherText
    aesKey = $AesKey
}
Write-Host "Texto desencriptado: $decryptedText"

if ("$decryptedText" -ne "$PlainText") {
    throw "Fallo de validación: el texto desencriptado no coincide con el texto original."
}
Write-Host "OK: encriptación/desencriptación válida."
Write-Host ("Cuenta para abonos configurada: {0}" -f $AccountCredit)

if (-not [string]::IsNullOrWhiteSpace($UserName) -and -not [string]::IsNullOrWhiteSpace($PasswordPlain)) {
    Write-Host ""
    Write-Host "== Prueba 3: Autenticación básica =="
    $passwordEncrypted = Invoke-BanecoGet -Path "/api/authentication/encrypt" -Query @{
        text   = $PasswordPlain
        aesKey = $AesKey
    }

    $authResponse = Invoke-BanecoPost -Path "/api/authentication/authenticate" -Body @{
        userName = $UserName
        password = $passwordEncrypted
    }

    Write-Host ("responseCode: {0}" -f $authResponse.responseCode)
    Write-Host ("message: {0}" -f $authResponse.message)

    if ($authResponse.responseCode -ne 0) {
        throw "Fallo autenticación: $($authResponse.message)"
    }

    if ([string]::IsNullOrWhiteSpace($authResponse.token)) {
        throw "Fallo autenticación: no se recibió token."
    }

    Write-Host ("token (inicio): {0}..." -f $authResponse.token.Substring(0, [Math]::Min(30, $authResponse.token.Length)))
    Write-Host "OK: autenticación válida."
}
Write-Host ""
Write-Host "Resultado final: smoke test completado."
