import il from 'ilias';
import $ from 'jquery';
import PasswordToggle from './Form/PasswordToggle';
import PublicationUsage from './Form/PublicationUsage';
import PaellaPlayer from './Paella/paella-player.min.js'

il.Opencast = il.Opencast || {};
il.Opencast.Form = il.Opencast.Form || {};
il.Opencast.Form.passwordToggle = new PasswordToggle($);
il.Opencast.Form.publicationUsage = new PublicationUsage($);

il.Opencast.Paella = il.Opencast.Paella || {};
il.Opencast.Paella.player = PaellaPlayer;
