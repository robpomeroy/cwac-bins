# SMTP credentials need to be in the SMTP.creds file
$credsFile = "$PSScriptRoot\SMTP.creds"
if(Test-Path -Path $credsFile) {
    $creds = Import-CliXml -Path $credsFile
} else {
    Throw "Error: $credsFile not found"
}

# Load configuration
$ConfigFile = "$PSScriptRoot\smtp.conf"
If(!(Test-Path $ConfigFile)) {
    Throw "Error: configuration file $ConfigFile missing! Check smtp.conf.example."
}
foreach ($line in $(Get-Content $ConfigFile)){
    if($line -ne "" -and -not($line.StartsWith('#'))) {
        Set-Variable -Name $line.split("=")[0] -Value $line.split("=",2)[1] -Option ReadOnly
    }
}

# We could run the script within WSL, with a script that calls the PHP script...
# $collectionDates = (wsl -e /path/to/script) # WSL/Linux

# But this also runs under Windows, provided PHP and Geckodriver are installed
$collectionDates = (php "$PSScriptRoot\getDates.php") # Windows

# Replace all | with CRLF
$collectionDates = $collectionDates.Replace("|","`r`n")

# Trim string and check if it's empty
if ($collectionDates.Trim() -eq "") {
    $collectionDates = "ERROR: Script failed to return any content"
}

# Split $Recipients on ',' and convert to array
$emailTo = $Recipients.Split(",")
 
# Create the message object (using a .NET component with SMTP auth capability)
$message = New-Object Net.Mail.MailMessage
$message.From = $Sender
foreach ($to in $emailTo) {
    $message.To.Add($to)
}
$message.Subject = "Next bin collection dates"
$message.Body = $collectionDates

# Set up and send the email
$smtp = New-Object Net.Mail.SmtpClient($SMTPserver, $SMTPport)
$smtp.EnableSSL = 1
$smtp.Credentials = $creds
$smtp.Send($message)
$message.Dispose()
