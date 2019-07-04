import Injector from 'lib/Injector'; // eslint-disable-line
import WithSudoMode from '../containers/SudoMode/SudoMode';

export default () => {
  // When the silverstripe/mfa module is installed, apply sudo mode to it
  Injector.transform('apply-sudo-mode-to-mfa', (updater) => {
    updater.component('RegisteredMFAMethodListField', WithSudoMode);
  });
};
