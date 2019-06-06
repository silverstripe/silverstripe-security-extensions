import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classnames from 'classnames';

class LoadingIndicator extends PureComponent {
  render() {
    const { className, size, block } = this.props;

    const classNames = classnames('ss-loading-indicator', className, {
      'ss-loading-indicator--block': block,
    });

    return <div style={{ height: size, width: size }} className={classNames} />;
  }
}

LoadingIndicator.propTypes = {
  className: PropTypes.string,
  block: PropTypes.bool,
  size: PropTypes.string,
};

LoadingIndicator.defaultProps = {
  block: false,
  size: '6em',
};

export default LoadingIndicator;
