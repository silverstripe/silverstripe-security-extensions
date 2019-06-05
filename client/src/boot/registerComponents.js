import Injector from 'lib/Injector';
import LoadingIndicator from '../components/LoadingIndicator/LoadingIndicator';

export default () => {
  Injector.component.registerMany({
    LoadingIndicator,
  });
};
