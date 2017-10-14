# CUCM-appuser-devices
PHP Script for bulk add or remove devices to CUCM appuser using AXL.
## Installation
```
git clone https://github.com/moki74/cucm-appuser-devices.git
```
## Usage
In conf dirctory modify cucm.conf file :
```
host = IP address of CUCM
username = username for AXL user
password = pass for AXL user
cucm_version= 9.1  
```
cucm_version param holds directory name where you put wsdl file for yours versio of CUCM.
if you have another version - make directory with name of that version  8.5 and put
AXL soap files in that directory. 
You can download files from CUCM Application->Plugins-CiscoAxlToolkit ,
files will be in schema directory. 

Call script with appuser name and action that you want - it can be add or del.
For adding phones :
In phones.txt add names of phones, for this example let it be : SEP0C116781ACBE, SEP0C116781ACDE.
You always need to use this file - phones.txt Script will not work with custom file.
Delimiter for phone names  can be ",",".","|",":","\n".

We will use TestJTapi user for this example where wi will add teo phones.

```
php cucmappuser.php TestJtapi add
```
After executing script you'll find directory with name of appuser, and there you'll find two files:
log.txt and beforeLastUpdate.txt .
log.txt contains informations of success or failed updates (name of phones that were not inserted).
Possible reasons for that are :
1. Phones are allready in appuser.
2. Phone name does not exists in CUCM.
Check phone names again to fix this problem.

beforeLastUpdate.txt contains names of all phones that were inserted in appuser before modifications,
so you can revert to previous state.

If you want to remove phones procedure is the same but you call script with del option.
Again - we will use  TestJtapi appuser for this example :
```
php cucmappuser.php TestJtapi del
```
### If you like this script and find it usefull consider making small donation in Bitcoin 
### BTC address : 1D3PDXSQDjvyLXeMb34XR6UeCwZX7tcjXP
### Thanks.

