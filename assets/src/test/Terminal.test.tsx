import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { Terminal } from '../components/ui/Terminal';

describe('Terminal', () => {
  it('renders all log lines', () => {
    render(<Terminal lines={['Line one', 'Line two', 'Line three']} />);
    expect(screen.getByText('Line one')).toBeInTheDocument();
    expect(screen.getByText('Line three')).toBeInTheDocument();
  });

  it('shows cursor when isRunning=true', () => {
    const { container } = render(<Terminal lines={[]} isRunning={true} />);
    expect(container.querySelector('[aria-hidden]')).toBeInTheDocument();
  });

  it('hides cursor when isRunning=false', () => {
    const { container } = render(<Terminal lines={[]} isRunning={false} />);
    expect(container.querySelector('[aria-hidden]')).not.toBeInTheDocument();
  });

  it('renders nothing extra when no lines', () => {
    render(<Terminal lines={[]} />);
    expect(screen.queryByText(/01/)).not.toBeInTheDocument();
  });

  it('displays line numbers', () => {
    render(<Terminal lines={['First', 'Second']} />);
    expect(screen.getByText('01')).toBeInTheDocument();
    expect(screen.getByText('02')).toBeInTheDocument();
  });

  it('renders custom title', () => {
    render(<Terminal lines={[]} title="build output" />);
    expect(screen.getByText('build output')).toBeInTheDocument();
  });
});
