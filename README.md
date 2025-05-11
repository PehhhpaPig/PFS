# PFS V 0.8.0

## Install Instructions using XAMPP:
1. All files should be added to XAMPP htdocs folder, using the heirarchy as it appears in github (ie. htdocs/PFS/api/... and htdocs/PFS/public/... etc)
2. When all are added, start apache and mySQL. Create a database at browser address: http://localhost/phpmyadmin/index.php, and name it NuTracker.
3. Under the SQL tab in this database, run all the queries inside PFS/init_db.php by hand (soz, it no longer works automatically, am trying to fix but am just a vibe coder)
4. Once database is initialised and all files and folders are installed correctly (as shown in github repo), you can go to http://localhost/PFS/public/index.html.
5. You can log-in as username: admin / password: [message me for password], or create a new account.
6. If you create a new account, you will be forced to add 2FA by scanning a QR code inside either Google Authenticator, or Microsoft Authenticator.
You must then use these Authenticator codes to log in to your account each time.

## Aside:
I apologise that this is very much annoying.
If at any stage you get an HTML 500 error, lemme know and I can try to remedy.

## Security Features Digest:
Implemented Security Features:
1. Front-end vs Back-end seperation. Front-end only contains .html and JavaScript files. All back-end files (.php) are not shown to user, instead uses POST request to communicate with these back-end files. This prevents users from viewing application files source code. 
2. Session ID Cookies 128 bits in length with 64 bits of entropy.
3. Session ID Cookies have 0 lifetime (Requires logging back in with new session everytime session is killed).
4. Passwords hashed and salted using BCrypt (Method: Hash(Password + Salt) = 60char hash including appended salt.
5. Mandatory 2FA Authentication implemented using a TOTP (Time-based One Time Password) secret - scanned and added to authenticator app of choice via generated QR code to generate passcodes.
6. TOTP Secrets for 2FA encrypted with Sodium Crypto Secret Box (Takes Encryption key stored on-server in .env in combination with a 24-byte nonce to encrypt TOTP secrets for database storage). This means attackers cannot simply breach the database and acquire 2FA secrets of users, they would also have to compromise the entire server to access .env encryption key.
7. Password Bruteforce protection implemented as a timeout of increasing length after several incorrect password attempts for a given username and given IP address.
8. 2FA code bruteforce protection implemented as a timeout of increasing length after 3 incorrect 2FA code entries for given username+correct password and given IP address. Could run the maths to figure out how long a 2FA breach would statistically take.
9. Strict types enforced (ie. if an argument is expecting an integer and receives a float, it will error out rather than attempting to cast to int).
10. Rudimentary user-input sanitisation (white spaces stripped, length enforced, 2FA codes only checked numerically using RegEx). <-- More work needs doing here

Future Security Features:
1. Much more heavy duty user-string sanitisation (RegEx would be a friend here).
2. Encrypt database for prod-build.
3. Purge database credentials list (add secure password to root, remove additional account).
4. Anything else?
   

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
