#!/usr/bin/python

import subprocess

if subprocess.call("sudo apt-get -y install apache2",  shell=True) > 0:
	print('Error')
	exit(1)
else:
	print('Apache installed successfully')

