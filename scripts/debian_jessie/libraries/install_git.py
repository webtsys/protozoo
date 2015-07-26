#!/usr/bin/python

import subprocess

if subprocess.call("sudo apt-get -y install git",  shell=True) > 0:
	print('Error')
	exit(1)
else:
	print('Git installed successfully')

