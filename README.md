# ILIAS-Plugin Opencast

This version of the ILIAS plugin for Opencast is operated and developed collaboratively by a community (for mailing list see: https://opencast.org/communication/). The University of Bern acts as coordinative maintainer (contact: info.ilub@unibe.ch). In this role, the University of Bern organizes monthly meetings (see: https://opencast.org/webmeetings/), discusses ideas for further development with the community and refers to existing development companies for the implementation of these ideas.

Use Opencast in ILIAS LMS with a wide variety of features:
* create Opencast series as repository objects
* upload, schedule and edit events
* watch and download videos or use the Annotation tool
* integrated Paella video Player
* live stream events with an on-screen chat
* detailed permission configuration
* use Opencast videos in the ILIAS page editor (additional plugin required: https://github.com/opencast-ilias/OpencastPageComponent)

## Getting Started

### Requirements
The plugin is published in several branches, each of which is compatible with one ILIAS version. The dependencies are as follows:

| Branch    | ILIAS Version | PHP Versions |
|-----------|---------------|--------------|
| release_7 | 7.0 - 7.999   | 7.3.x, 7.4.x |
| release_8 | 8.0 - 8.999   | 7.4.x, 8.0.x |

### Preconditions to update/migrate to v5.x and higher
If you want to update to v5.x or higher of this plugin or migrate from other ILIAS plugins to v5.x or higher
of this plugin for Opencast please check the following readme: [migration](./doc/migration.md).

### Installation
Start at your ILIAS root directory

```bash
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject/
cd Customizing/global/plugins/Services/Repository/RepositoryObject/
git clone https://github.com/opencast-ilias/OpenCast.git
```

ILIAS < 8: As ILIAS administrator go to "Administration"->"Plugins" and 
install/activate the plugin.
ILIAS >= 8: You can install the plugin using CLI, see https://github.com/ILIAS-eLearning/ILIAS/blob/release_8/setup/README.md

### Configuration
After a fresh installation, the plugin configuration will already contain a basic configuration. A few things will have to be adjusted to make the plugin work though. Have a look at the [configuration manual](./doc/CONFIGURATION.md).

### Installation Live Chat
This is only required if the plugin is configured to show live events and if you wish to display a live chat during these events.

#### Install Node JS
The live chat runs on a node js server and was tested with node version 12.x. To install nodejs v10.x on Ubuntu, execute:
```bash
curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -
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

## License

This project is licensed under the GPL v3 License
