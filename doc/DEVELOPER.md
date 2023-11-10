# References

## Component READMEs
Many components in the /src directory have a README to explain some concepts or the current state
of the component. Start at the base [README](../src/README.md).

## Configuration README
A more detailed description of the whole plugin configuration: [README](./CONFIGURATION.md).

## Opencast Documentation
https://docs.opencast.org/

Both admin and developer documentation offer a good base to understand Opencast's structure and features.
For developing the plugin, most of all the developer doc's external API description comes in handy.

## Opencast developer mailing list
dev@opencast.org

https://groups.google.com/a/opencast.org/g/dev

## Public Test servers
https://develop.opencast.org/

https://stable.opencast.org/

## ILIAS Paella Player
### Development:
in order to update, develop and extend the ilias paella player:
1. make sure you are in the ({plugin-root-dir}/js/opencast/src/Paella) directory.
2. open a terminal and run `npm install && npm run dev`

#### Updating, develop, extend the pallea player library:
<b>Directory: {plugin-root-dir}/js/opencast/src/Paella</b>
<b>Entry file: player.js</b>
<b>Output file: paella-player.min.js</b>
##### For update the paella libs:
1. make sure you are in ({plugin-root-dir}/js/opencast/src/Paella) directory.
2. open a terminal and run the npm install or update commande like this: `npm install paella-core@8.0`
...

##### development:
1. run `npm run dev` command in a terminal in the directory ({plugin-root-dir}/js/opencast/src/Paella), which brings up the watch from webpack.
2. Start changing the ({plugin-root-dir}/js/opencast/src/Paella/player.js)

##### production:
1. make sure you are in the current directory ({plugin-root-dir}/js/opencast/src/Paella).
2. open a terminal and run the command `npm run build`
NOTE: make sure the node_modules folder is not included in your commit!

---
A small hint for developers: it is possible to test the player in livestream mode just be opening the "test_paella_livestream.html" file directly in a browser without having to have livestream test environment from Opencast!
The link would be something like:
{http://localhost}/Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/test_paella_livestream.html
change the {http://localhost} part according to your setup!

