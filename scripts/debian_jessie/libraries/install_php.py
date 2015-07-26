#!/usr/bin/python3

import sys
import subprocess
import argparse
import platform
import shutil

pyv=platform.python_version_tuple()

if pyv[0]!='3':
	print('Need python 3 for execute this script')
	sys.exit(1)

parser=argparse.ArgumentParser(description='Script for create a new php-fpm server.')

parser.add_argument('--conf_to_copy', help='Configuration file for php-fpm', required=True)

args = parser.parse_args()

#Dash, the default debian jessie shell, don't support <<<

if subprocess.call("sudo DEBIAN_FRONTEND=noninteractive apt-get -y install php5-cli php5-fpm php5-gd php5-mysqlnd",  shell=True) > 0:
	print('Error')
	sys.exit(1)
else:
	print('php-fpm installed successfully')

# Copying spanel.conf file

dest_file="/etc/php5/fpm/pool.d/"

#if not shutil.copy(args.conf_to_copy, dest_file):
#	print("Error: cannot copy"+args.conf_to_copy+" in /etc/php5/fpm/pool.d/")
#sys.exit(1)

if subprocess.call("sudo cp "+args.conf_to_copy+" "+dest_file+"",  shell=True) > 0:
	print('Error')
	sys.exit(1)
else:
	print('php-fpm configuration installed')
	
if subprocess.call("sudo systemctl restart php5-fpm",  shell=True) > 0:
	print('Error reloading php5-fpm')
	sys.exit(1)
else:
	print('php-fpm reloaded successfully')


