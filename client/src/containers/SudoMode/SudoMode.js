import React, { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * Provides a HOC wrapper that will enforce "sudo mode".
 *
 * This checks that the user has verified that they are them via password
 * entry within a certain period of time since they originally logged in.
 * If this state is not active then they will be presented with a notice,
 * then a verification form, then the passed through component will be
 * rendered as normal.
 *
 * Note that any backend controllers that accept XHR requests from wrapped
 * components should enforce backend sudo mode checks, while they can
 * assume that sudo mode would be active before requests are actually made
 * to them via legitimate use paths.
 */
const withSudoMode = (WrappedComponent) => {
  class ComponentWithSudoMode extends Component {
    constructor(props) {
      super(props);

      this.state = {
        active: props.sudoModeActive || false,
        showNotice: true,
      };
    }

    /**
     * Returns whether "sudo mode" is active for the current user.
     *
     * @returns {boolean}
     */
    isSudoModeActive() {
      return this.state.active === true;
    }

    renderSudoModeNotice() {
      return <p>Sudo mode notice</p>;
    }

    renderSudoModeVerification() {
      return <p>Sudo mode verification</p>;
    }

    /**
     * Renders the "sudo mode" notice or verification screen
     *
     * @returns {HTMLElement}
     */
    renderSudoMode() {
      const { showNotice } = this.state;
      if (showNotice) {
        return this.renderSudoModeNotice();
      }
      return this.renderSudoModeVerification();
    }

    render() {
      const passProps = {
        ...this.props,
      };

      if (this.isSudoModeActive()) {
        return <WrappedComponent {...passProps} />;
      }

      return this.renderSudoMode();
    }
  }

  ComponentWithSudoMode.propTypes = {
    sudoModeActive: PropTypes.bool,
  };

  ComponentWithSudoMode.defaultProps = {
    sudoModeActive: false,
  };

  return ComponentWithSudoMode;
};

export default withSudoMode;
