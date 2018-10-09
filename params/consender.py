#!/usr/bin/python
import socket as sss
import time

s=sss.socket(sss.AF_UNIX)
s.connect('/var/run/energo.soc')
s.settimeout(0.1)
while True:
	txt=raw_input('>>')
	if txt[0]=='#':
		try:
			print '<<', s.recv(1024)
		except:
			print '<no data>'
		continue
#convert string
	x = 0
	dd = []
	while x < len(txt):
		if txt[x] == '$':
			c = txt[x+1:x+3]
			x += 3
			n = c.decode('hex')
			dd += [n]
		else:
			dd += [txt[x]]
			x += 1

	res = ''.join(dd)
	s.send(res)
