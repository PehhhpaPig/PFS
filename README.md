# PFS V 1.1.5

## Install Instructions using XAMPP:

# For macOS
1. With XAMPP installed:
2. If not already installed, install HomeBrew (https://brew.sh/) onto MacOS using /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   -- Use the website instructions if unsure of this, or ask me
3. Download all code files as ZIP. Extract these files, rename the project folder from "pfs-main" to "PFS".
4. Place this folder into HTDOCS, alongside the existing masteryLevels folder. Eg htdocs should contain a MasteryLevels folder, and also a PFS folder.
5. Start mySQL and Apache inside the XAMPP manager
6. In terminal, type command: brew install php
7. Once install has completed, type command: php init_db.php
8. This should print "Schema imported and sample data seeded \n Database and user initialised"
9. If all these steps have completed correctly, you should now be able to visit localhost/PFS/public/index.html in a web browser.
10. You can log-in as a demo account without 2FA authentication, or alternately you can register a new account. 
11. If you create a new account, you will be forced to add 2FA by scanning a QR code inside either Google Authenticator, or Microsoft Authenticator.
You must then use these Authenticator codes to log in to your account each time.
12. Please let me know if you have any issues, I can try to fix.
    
# For Ubuntu-Linux (I have only tested these during development on my own computer, as such they have not been verified with a clean 3rd party install)
1. With XAMPP installed, and Apache Web Server and mySQL Database both running:
2. All files should be added to XAMPP htdocs folder (typically /opt/lampp/htdocs/), using the heirarchy as it appears in github (ie. /opt/lampp/htdocs/PFS/api/... and /opt/lampp/htdocs/PFS/public/... etc)
3. When all are added, enter command: % /opt/lampp/bin/php /opt/lampp/htdocs/PFS/init_db.php
3. This will run the database initilisation. An error message during installation about a missing Sodium installation /should/ be ok, as missing Sodium functions are automatically handled using Composer.
4. Once database is initialised and all files and folders are installed correctly (as shown in github repo), you can go to http://localhost/PFS/public/index.html.
5. You can log-in as a demo account without 2FA authentication, or alternately you can register a new account. 
6. If you create a new account, you will be forced to add 2FA by scanning a QR code inside either Google Authenticator, or Microsoft Authenticator.
You must then use these Authenticator codes to log in to your account each time.


## Security Features Digest:
Implemented Security Features:
1. Front-end vs Back-end seperation. Front-end only contains .html and JavaScript files. All back-end files (.php) are not shown to user, instead uses POST request to communicate with these back-end files. This prevents users from viewing application files source code. 
2. Session ID Cookies 128 bits in length with 64 bits of entropy.
3. Session ID Cookies have 0 lifetime (Requires logging back in with new session everytime session is killed).
4. Passwords hashed and salted using BCrypt (Method: Hash(Password + Salt) = 60char hash including appended salt.)
5. Mandatory 2FA Authentication implemented using a TOTP (Time-based One Time Password) secret - scanned and added to authenticator app of choice via generated QR code to generate passcodes.
6. TOTP Secrets for 2FA encrypted with Sodium Crypto Secret Box producing 94char encrypted TOTP secret (Takes Encryption key stored on-server in .env in combination with a 24-byte nonce to encrypt TOTP secrets for database storage). This means attackers cannot simply breach the database and acquire 2FA secrets of users, they would also have to compromise the entire server to access .env encryption key.
7. Password Bruteforce protection implemented as a timeout of increasing length after several incorrect password attempts for a given username and given IP address.
8. 2FA code bruteforce protection implemented as a timeout of increasing length after 3 incorrect 2FA code entries for given username+correct password and given IP address. Double check my maths on this, but should statistically take ~16 months of bruteforcing from static IP address to bruteforce TOTP code (7/1000000 chance of correctly guessed code per attempt, after initial 18 wrong attempts lockout is maxed at 12 attempts allowed per hour). Obviously, dynamic IP cycling would circumvent this, probs should look into lockout for username rather than IP.
9. Strict types enforced (ie. if an argument is expecting an integer and receives a float, it will error out rather than attempting to cast to int).
10. Rudimentary user-input sanitisation (white spaces stripped, length enforced, 2FA codes only checked numerically using RegEx). <-- More work needs doing here
11. CAPTCHA added to stop bruteforce attacks against login credentials and 2FA TOTP verification codes on login and password reset. If needed, CAPTCHA difficulty can be increased.
12. User passwords for registration and password resets are mandated to be at least 8 characters including symbol, uppercase and lowercase, and number chars.
13. String sanitisation on user inputs to prevent injection, overflow, etc. (Usernames, passwords, 2FA, RFID Tags, Item Data arrays) - RegEx *was* a friend here.
14. Data-field auto-filling disabled.
15. Database credentials list automatically purged upon initialisation, and root is both locked and denied for remote access.
16. All front-end facing files (JavaScript, HTML, CSS) have been obfuscated to prevent users from easily determining the structure of the application/server stack. For development purposes, these obfuscated files are only included in the 'Obfuscated_FrontEnd_Version' branch.

Future Security Features:
1. ~~More heavy duty user-string sanitisation (RegEx would be a friend here)~~ ✅Completed!✅
2. ~~User password strength mandates~~✅Completed!✅
3. *Encrypt database for prod-build.* ❕This will not be done for the purpose of demoing for assignment, but should be implemented in practice were this to enter real production❕
4. ~~Purge database credentials list (add secure password to root, remove additional account).~~ ✅Completed!✅
5. *Configure Apache to not display file endings in URL (eg. https://localhost/PFS/index.html should only display as https://localhost/PFS/index or equivalent address).* ❕Will be done before production. This is a local change to XAMPP config, so will not be implemented through GitHub❕
6. ~~Add CAPTCHA to further protect against bot bruteforcing (this is proving harder than expected)~~ ✅Completed!✅
7. ~~Anything else~~ ❌no time lol❌
   

⠀⠀⠀⣴⣿⠟⠁⠀⠀⠀⠀⠀⠀⠀⠘⣿⣷⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠉⠛⠿⣿⣿⣶⣶⣿⣇⠀⠀⣼⣿⠇⠀⠀⢀⣴⣿⠟⠉⠉⣿⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⣼⣿⠇⢀⣤⣄⠀⠀⠀⠀⠀⠀⠀⣿⣿⠃⠀⠀⠀⠀⠀⠀⣠⣴⣿⠿⣿⣷⣄⠀⠉⢻⣿⣿⣷⣶⣿⡇⠀⠀⢠⣿⡟⠁⠀⠀⢀⣿⡿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⢰⣿⠏⠀⣿⣿⣿⠀⢀⣴⣶⡄⠀⠀⣿⣿⠀⠀⠀⠀⠀⢀⣾⡿⣋⣴⣄⠀⢿⣿⠀⠀⠈⠛⠀⢩⣿⡿⣿⣷⣦⣿⠟⠀⠀⠀⢀⣾⡿⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⣿⣿⠀⠀⠈⠙⠉⠀⢸⣿⣿⡏⠀⣸⣿⡇⠀⠀⠀⠀⠀⢸⣿⠃⣿⣿⡿⠀⣼⣿⠀⠀⠀⠀⠀⠀⠛⠀⠀⠙⢿⣿⣷⣤⠀⣠⣿⡿⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⣿⣿⠀⠀⠀⠀⠀⠀⠀⠈⠉⠀⣠⣿⡟⠀⠀⠀⠀⠀⠀⠘⣿⣧⣈⣉⣠⣼⣿⠟⠀⢀⣴⣾⣿⣷⣦⣄⠀⠀⠉⠉⠻⣿⣿⣿⡟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⢻⣿⡀⠀⠀⠀⠀⠀⠀⠀⠀⣰⣿⡿⠁⠀⠀⠀⠀⠀⠀⠀⠈⠻⠿⠿⠿⠛⠁⠀⢀⣾⡟⢉⣩⣙⢿⣿⡆⠀⠀⠀⠸⣿⠟⣿⣿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⢿⣿⣄⡀⠀⠀⠀⢀⣤⣾⣿⠟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⠁⢸⣿⣿⠂⣿⡿⠀⠀⠀⠀⠀⠀⠈⢻⣿⣆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠈⢿⣿⣿⣿⣶⣿⣿⠿⠛⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢻⣿⡄⠈⢉⣡⣾⣿⠇⠀⠀⠀⠀⠀⠀⠀⠀⠹⣿⣆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠹⣿⣿⣍⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠻⢿⣿⡿⠿⠛⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢻⣿⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠈⠻⣿⣷⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⣿⣷⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠈⠙⢿⣿⣷⣤⣤⡀⢠⣤⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⣤⣤⣤⣀⠀⠀⠀⠀⠀⠀⢿⣿⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⠻⠿⢿⣿⣿⣷⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⣾⠟⠋⠁⠀⠈⠛⢿⣦⡀⠀⠀⠀⢸⣿⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⠏⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢰⡿⠁⠀⠀⠀⠀⠀⠀⠀⠙⣷⡀⠀⠀⣼⣿⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⣿⡿⠀⠀⣿⣆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣿⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⡇⠀⠀⣿⣿⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⡇⠀⠀⢹⣿⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠸⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣾⠇⠀⢰⣿⡏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⡇⠀⠀⠀⠻⣿⣦⡀⠀⠀⣀⣤⣾⡿⠃⠀⠀⠀⠀⠀⢻⣇⠀⠀⠀⠀⠀⠀⠀⢀⣼⠏⠀⢀⣿⣿⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⣿⣷⡀⠀⠀⠀⠉⠻⠿⢿⣿⡿⠿⠋⠀⠀⠀⠀⠀⠀⠀⠈⢿⣦⡀⠀⠀⠀⣀⣴⡿⠋⠀⢠⣾⣿⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣹⣿⣧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⠻⠷⠶⠾⠛⠋⠀⠀⣠⣿⣿⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣿⡿⣿⣷⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⣿⣿⣿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⣀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⠇⠙⢿⣿⣷⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⣾⣿⡿⠋⠈⢻⣿⣆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⣿⣿⣦⣄⣀⣠⣤⣤⣶⣶⣿⣿⡿⠀⠀⠀⠉⠻⣿⣿⣶⣦⣄⣀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣀⣤⣴⣾⣿⡿⠟⠁⠀⠀⣀⣀⢿⣿⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⢀⣤⣬⣿⣿⣿⣿⣿⣿⡿⠿⠛⢹⣿⡇⠀⠀⠀⠀⠀⠀⠉⠛⠿⣿⣿⣿⣿⣶⣶⣶⣶⣶⣶⣶⣿⣿⣿⡿⠟⠋⠁⠀⠀⠀⠀⠀⠻⣿⣿⣿⣿⣦⣤⣀⠀⠀⠀⣀⣤⣶⡄
⠘⠻⠟⣿⣿⡟⠉⠀⠀⠀⠀⠀⢸⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠉⠉⠙⠛⠛⠛⠉⠉⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⢹⣿⣿⣿⣿⣿⣷⣾⣿⣿⠛⠁
⠀⠀⠀⣿⣿⠁⠀⠀⠀⠀⠀⠀⢸⣿⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⡇⠀⠉⠉⠉⣿⣿⣿⣶⣶
⠀⠀⠀⠙⠛⠀⠀⠀⠀⠀⠀⠀⠀⣿⣧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⡇⠀⠀⠀⠀⢸⣿⣿⠉⠉
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢿⣿⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣾⣿⡇⠀⠀⠀⠀⢸⣿⠟⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⢿⣷⡄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⡟⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⢿⣿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣾⣿⠟⠁⠀⠀⣤⣶⣿⣶⡄⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠻⣿⣷⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣴⣶⠀⠀⠀⠀⢀⣠⣶⣿⣿⣿⣄⠀⠀⢸⣿⠋⢙⣿⡇⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠛⢿⣿⣦⣤⣀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣿⣿⣄⣠⣤⣾⣿⣿⠟⠋⠀⠻⣿⣷⣤⣸⣿⣦⣿⡿⠃⢀⣤⣤
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠻⣿⣿⣿⣶⣶⣶⣦⣤⣤⣤⣤⣤⣤⣴⣶⣾⣿⣿⣿⡿⠿⠛⠉⠀⠀⠀⠀⠀⠉⠻⠿⢿⣿⣿⣿⣷⣶⣿⣿⡿
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣿⣿⡏⠉⠛⠛⠛⠛⠛⠛⠛⠛⠛⠛⠛⠛⣿⣿⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠛⠛⠛⠛⠉⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣤⣤⣤⣤⣄⣀⣿⣿⡇⠀⠀⠀⠀⠀⠀⣀⣀⣀⡀⠀⠀⠀⣿⣿⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣀⡀⠀⠀
