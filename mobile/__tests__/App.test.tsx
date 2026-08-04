/**
 * @format
 */

import React from 'react';
import ReactTestRenderer from 'react-test-renderer';

import App from '../App';
import { colors, screen, spacing } from '../src/theme';

test('renders correctly', async () => {
    let renderer: ReactTestRenderer.ReactTestRenderer;

    await ReactTestRenderer.act(() => {
        renderer = ReactTestRenderer.create(<App />);
    });

    expect(renderer!).toBeDefined();
    expect(colors.primary).toMatch(/^#/);
    expect(spacing.lg).toBeGreaterThan(0);
    expect(screen.minTouchTarget).toBeGreaterThanOrEqual(44);

    await ReactTestRenderer.act(() => renderer!.unmount());
});
