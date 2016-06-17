SAMPLE PLUG-IN TWO FACTOR LOGIN


Goal:

This is sample of two factor authentication:
(see en.wikipedia.org/wiki/Two-factor_authentication)

It requires a user account with a registered mobile phone number. The
user needs to have access to his mobile phone, because the (1 time) 
password will be sent by SMS.


Installation and configuration:

Install and activate the plug-in. It requires the SMS sample plug-in
to be installed and configured.

For each user who requires SMS login, fill in a complete mobile phone
number in its "Location" field in the User Maintenance admin page.
Also concider to fill in the "Password Expiration in Days" field.


Operation:

Use the normal login of any Enterprise client application. 
Before login, the plug-in checks if either the password is empty, or if
the password has expired. If either is the case, and the user has a 
phone number defined, a new password will be generated and stored in
the system with a validity for 60 seconds. Then the new password is sent
to the user's mobile phone and this first login attempt will fail.


Exception:

Because CS does not allow filling in an empty password, the user may
also type one question character "?" which is detected by the plugin
as well and handled the same as leaving the password empty.
