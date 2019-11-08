# ILIAS-Plugin OpenCast

### Installation
Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject/
cd Customizing/global/plugins/Services/Repository/RepositoryObject/
git clone https://github.com/studer-raimann/OpenCast.git
```
As ILIAS administrator go to "Administration"->"Plugins" and install/activate the plugin.

### Workaround for internal Paella Player
If you're using the plugin-internal Paella Player, you will have to implement a small workaround for a bug in the current version of Paella Player. Start in you ILIAS root directory:
```bash
ln -s Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/node_modules/paellaplayer/build/player/resources/ .
```

### Adjustment suggestions
* Adjustment suggestions by pull requests
* Adjustment suggestions which are not yet worked out in detail by Jira tasks under https://jira.studer-raimann.ch/projects/PLOPENCAST
* Bug reports under https://jira.studer-raimann.ch/projects/PLOPENCAST
* For external users you can report it at https://plugins.studer-raimann.ch/goto.php?target=uihk_srsu_PLOPENCAST

### Installation Live Chat

#### Install Node JS
The live chat runs on a node js server and was tested with node version 10.x. To install nodejs v10.x on Ubuntu, execute:
```bash
curl -sL https://deb.nodesource.com/setup_10.x | sudo -E bash -
sudo apt-get install -y nodejs 
```
For other OS, see https://nodejs.org/en/download/package-manager/

#### Run the chat server
To run the chat server on http (see chapter "SSL"), execute the following:
```bash
node [PATH_TO_OPENCAST_PLUGIN]/src/Chat/node/server.js -c [CLIENT_ID] > [PATH_TO_LOG_FILE] 2>&1
```
Note that the chat server needs at least an ILIAS client id, which is used to establish a connection to the correct database (multiple clients are not supported yet). Depending on the server configuration, some more parameters might be necessary. Add the parameter '-h' for a list of all possible parameters and their default values.


#### SSL
If your web server uses HTTPS, you will need to make the chat understand HTTPS as well. This can be achieved in two different ways:

1. **(recommended)** Run the chat server with https, by passing the argument '--use-https'. You will also need to pass the paths to the ssl certificate and key, and the passphrase for the key (arguments '--ssl-cert-path', '--ssl-key-path' and '--ssl-passphrase'). Example:
   * `node src/Chat/node/server.js -c default --use-https --ssl-key-path /etc/apache2/ssl/serverkey.pem --ssl-cert-path /etc/apache2/ssl/servercert.pem --ssl-passphrase password123`

2. Configure a reverse proxy on your web server, which translates https requests to http and passes them to the chat server. If needed, the ip and port to which the chat server listens to can be passed as arguments via '--ip' and '--port'.

 Note that when using a reverse proxy, the chat will open an unsecured websocket (ws://), whereas the first option will open a secured one (wss://).


### ILIAS Plugin SLA
Wir lieben und leben die Philosophie von Open Source Software! Die meisten unserer Entwicklungen, welche wir im Kundenauftrag oder in Eigenleistung entwickeln, stellen wir öffentlich allen Interessierten kostenlos unter https://github.com/studer-raimann zur Verfügung.

Setzen Sie eines unserer Plugins professionell ein? Sichern Sie sich mittels SLA die termingerechte Verfügbarkeit dieses Plugins auch für die kommenden ILIAS Versionen. Informieren Sie sich hierzu unter https://studer-raimann.ch/produkte/ilias-plugins/plugin-sla.

Bitte beachten Sie, dass wir nur Institutionen, welche ein SLA abschliessen Unterstützung und Release-Pflege garantieren.
