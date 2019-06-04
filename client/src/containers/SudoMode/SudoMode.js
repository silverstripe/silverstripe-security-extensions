import React, { Component } from 'react';
// import PropTypes from 'prop-types';

const withSudoMode = (WrappedComponent) => (
  // eslint-disable-next-line react/prefer-stateless-function
  class ComponentWithSudoMode extends Component {
    render() {
      const passProps = {
        ...this.props,
      };

      return (
        <div>
          <p>Wrapped sudo mode component here</p>
          <WrappedComponent {...passProps} />
        </div>
      );
    }
  }
);

export default withSudoMode;
