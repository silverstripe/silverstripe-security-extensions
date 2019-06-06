/* global jest, describe, it, expect */
import React from 'react';
import Enzyme, { shallow } from 'enzyme';
import Adapter from 'enzyme-adapter-react-16';
import LoadingIndicator from '../LoadingIndicator';

Enzyme.configure({ adapter: new Adapter() });

describe('LoadingIndicator', () => {
  describe('render()', () => {
    it('can be displayed as "block"', () => {
      const wrapper = shallow(
        <LoadingIndicator block />
      );

      expect(wrapper.find('.ss-loading-indicator--block')).toHaveLength(1);
    });

    it('allows extra classes to be provided', () => {
      const wrapper = shallow(
        <LoadingIndicator className="hello-world" />
      );

      expect(wrapper.find('.ss-loading-indicator.hello-world')).toHaveLength(1);
    });
  });
});
