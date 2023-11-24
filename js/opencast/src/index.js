import il from 'ilias';
import $ from 'jquery';
import PasswordToggle from './Form/PasswordToggle';
import PublicationUsage from './Form/PublicationUsage';
import WaitOverlay from './UI/WaitOverlay';
import StartWorkflow from './Workflow/StartWorkflow';
import PaellaPlayer from './Paella/paella-player.min.js'

il.Opencast = il.Opencast || {};
il.Opencast.Form = il.Opencast.Form || {};
il.Opencast.Form.passwordToggle = new PasswordToggle($);
il.Opencast.Form.publicationUsage = new PublicationUsage($);

il.Opencast.UI = il.Opencast.UI || {};
il.Opencast.UI.waitOverlay = new WaitOverlay($);

il.Opencast.Workflow = il.Opencast.Workflow || {};
il.Opencast.Workflow.startWorkflow = new StartWorkflow($);

il.Opencast.Paella = il.Opencast.Paella || {};
il.Opencast.Paella.player = PaellaPlayer;
