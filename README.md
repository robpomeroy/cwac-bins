# Bin collection schedule retriever (Cheshire West and Cheshire)

Cheshire West and Chester don't currently offer a notification service to remind
you which bins should be left for collection. I'm forgetful and the collection
is on a two-week cycle, so having a timely reminder really helps me to get the
week right.

The `getDates.php` script fires up a headless Firefox browser, logs into the
[bin collection website](https://www.cheshirewestandchester.gov.uk/residents/waste-and-recycling/your-bin-collection/collection-day),
and gets the dates of the next 4 collections. You'll need to visit the CWAC
website at least once, to look up your address and find the correct format of
your postcode and first line of your address. Set these in the `.env` file. (See
`.env.example`.)

Run `composer install`, to install most dependencies.

## Install Geckodriver

This script depends on GeckoDriver, for headless Firefox.

### Linux/WSL

```bash
wget https://github.com/mozilla/geckodriver/releases/download/v0.33.0/geckodriver-v0.33.0-linux64.tar.gz
sudo tar zxf geckodriver-v0.33.0-linux64.tar.gz -C /usr/local/bin
sudo chmod +x /usr/local/bin/geckodriver
rm geckodriver-v0.33.0-linux64.tar.gz
```

### Windows

Download the latest
[Geckodriver release](https://github.com/mozilla/geckodriver/releases) for
Windows and place in the `PATH`.

## Set up PowerShell script

The included PowerShell script can call the PHP script and email the results. To
use it:

- Copy `smtp.conf.example` to `smtp.conf`` and edit the values
- Store your SMTP credentials in the same directory as the script by running
  `Get-Credential | Export-CliXml -Path '.\SMTP.creds'`

At some point, I might add email capabilities to the PHP script. I can barely
remember now why I did it this way round.

## Run as a scheduled Windows task

Set up a scheduled task to run the PowerShell script:

Program: `C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe`
Arguments: `-WindowStyle Minimized -File C:\Path\to\GetDates.ps1`

## Run this under WSL

You can run this as-is, under WSL. To call this from Windows, create a script
with contents similar to the following:

```bash
#!/bin/bash
/usr/bin/php /path/to/getDates.php
```

You can then call with `wsl -e /path/to/script`.
