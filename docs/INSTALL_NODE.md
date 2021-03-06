# Open Transcoding Platform Installation - Node Instructions

Everything inside the code blocks are things to enter via the command line unless otherwise noted.

Firstly, install Ubuntu 12.04 64bit Server. (Really, you should able to use any Debian-based distro, but I use the latest Ubuntu.)

Once installed, log in via whatever user you created, and set up the root user.

	sudo passwd root

Give it a good password. Or don't. Up to you. 

Now log out of the server and log back in as root. It's easier this way.

Next, just generally update and upgrade the new install.

	apt-get update
	apt-get -y upgrade
	
Next, we'll set up a repository to download HandBrake binaries.

	apt-get -y install python-software-properties
	add-apt-repository ppa:stebbins/handbrake-snapshots
	apt-get update

Now we start installing prerequisites.

	apt-get -y install build-essential libssl-dev curl handbrake-cli
	
Now install node.js

	cd /usr/src
	wget nodejs latest (i.e. wget http://nodejs.org/dist/v0.8.8/node-v0.8.8.tar.gz)
	tar xfz node-???
	cd node-???
	./configure
	make
	make install

You should be able to run "node -v" and get the version number.

Cool. Now let's set up some directories and connectivity with the MASTER server.

	mkdir /farm /farm/logs /farm/stuff /farm/stuff/in /farm/stuff/out

Okay, let's set up some trust with the MASTER server.

	ssh-keygen -t rsa

Leave everything on defaults, no passphrase.

Now copy this node's public key to the MASTER server. Replace "@farm" with "@your.master-server.com"

	ssh-copy-id -i /root/.ssh/id_rsa.pub farmer@farm

Type in "yes" to accept the host, and then try this command, again replacing "@farm" with your server hostname:

	ssh farmer@farm

That should let you into the MASTER server without having to input a password. `exit` out of the master server to get back to the farmer.

Cool? Awesome. Let's download the latest farmer.js script from the MASTER server:

	scp root@farm:/farm/node/farmer.js /farm/farmer.js
	
You again have to replace "@farm" with your server, along with the path to the farmer.js file on the MASTER server, and you'll need to input the MASTER server's root password.

You have the farmer.js node script on your NODE. You need to configure it!

	nano /farm/farmer.js
	
Inside, edit the configuration near the top of the file. There's a comment that says when you can stop editing.

Now to make sure it runs all the time.

	nano /etc/init/farmer.conf
	
Inside this file, add the following...
This is based on http://kevin.vanzonneveld.net/techblog/article/run_nodejs_as_a_service_on_ubuntu_karmic/

	description "farmer nodejs"
	author      "cyle gage"
	start on started mountall
	stop on shutdown
	
	# Automatically Respawn:
	respawn
	respawn limit 99 5
	
	script
		export HOME="/root"
		exec /usr/local/bin/node /farm/farmer.js >> /farm/logs/farmer.log 2>&1
	end script

That's it. Save it. Now we need to make it executable:

	chmod u+x /etc/init/farmer.conf
	
Now we can start this as a service on the server, and it'll start whenever the server boots. Start it:

	start farmer
	
Done. You have a node. 

Now you should see it in the Farming Admin Page, and you'll be able to enable it so the master starts giving it jobs.

Go make some more! One node is fine, but the point is that you need a farm to do multiple jobs at once.

## Troubleshooting

In case there are problems, you can set `debugmode = true` in farmer.js and run it in debug mode like so:

	node /farm/farmer.js
	
That way it will return some raw data about what's happening directly to the console.

## Updating/restarting

To update the farmer.js file and/or restart the farmer.js script, do the following. Again, replacing server names and paths where necessary:

	initctl stop farmer
	scp root@farm:/farm/node/farmer.js /farm/farmer.js
	initctl start farmer
	
You should be all set!