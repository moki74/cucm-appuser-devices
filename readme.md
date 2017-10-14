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
*cucm_version* param holds directory name where is wsdl file for your version of CUCM.
If your version that does not exist here - make directory with name of that version and put
AXL soap files in that directory. 
You can download files from CUCM Application->Plugins-CiscoAxlToolkit ,
files will be in *schema* directory. 

Call script with appuser name and action that you want - it can be **add** or **del**.

**For adding phones :**

In phones.txt add names of phones, for this example let it be : SEP0C116781ACBE, SEP0C116781ACDE.
You always need to use this file - **phones.txt** 
Script will not work with custom file.
Delimiter for phone names  can be ",",".","|",":","\n".

We will use TestJTapi user for this example where we will add phones.
Make sure you enter name of appuser exactly how it appears in CUCM - it's case sensitive.  

```
php cucmappuser.php TestJtapi add
```
After executing script you'll find directory with name of appuser, and there you'll find two files:
*log.txt* and *beforeLastUpdate.txt*

*log.txt* contains informations of success or failed updates (name of phones that were not inserted).
Possible reasons for that are :
	1. Phones are allready in appuser.
	2. Phone name does not exist in CUCM.
Check phones names again to fix this problem.

*beforeLastUpdate.txt* contains names of all phones that were inserted in appuser before modifications,
so you can revert to previous state.

For **removing phones** procedure is the same but you call script with del option, and in phones.txt phones
that will be removed.
Again - we will use  TestJtapi appuser for this example :
```
php cucmappuser.php TestJtapi del
```

##### If you like this script and find it useful consider making small donation in Bitcoin 
##### BTC address : 1D3PDXSQDjvyLXeMb34XR6UeCwZX7tcjXP
##### Thanks.

