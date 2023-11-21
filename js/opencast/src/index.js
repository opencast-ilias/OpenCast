import il from 'ilias';
import $ from 'jquery';
import PasswordToggle from './Form/PasswordToggle';
import WaitOverlay from './UI/WaitOverlay';
import PaellaPlayer from './Paella/paella-player.min.js'

il.Opencast = il.Opencast || {};
il.Opencast.Form = il.Opencast.Form || {};
il.Opencast.Form.passwordToggle = new PasswordToggle($);

il.Opencast.UI = il.Opencast.UI || {};
il.Opencast.UI.waitOverlay = new WaitOverlay($);

il.Opencast.Paella = il.Opencast.Paella || {};
il.Opencast.Paella.player = PaellaPlayer;
