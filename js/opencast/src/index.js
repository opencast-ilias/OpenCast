import il from 'ilias';
import $ from 'jquery';
import PasswordToggle from './Form/PasswordToggle';
import PublicationUsage from './Form/PublicationUsage';

il.Opencast = il.Opencast || {};
il.Opencast.Form = il.Opencast.Form || {};
il.Opencast.Form.passwordToggle = new PasswordToggle($);

il.Opencast.Form.publicationUsage = new PublicationUsage($);
