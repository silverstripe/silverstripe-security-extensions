import Injector from 'lib/Injector'; // eslint-disable-line
import LoadingIndicator from '../components/LoadingIndicator/LoadingIndicator';

export default () => {
  Injector.component.registerMany({
    LoadingIndicator,
  });
};
