import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { Badge } from '../components/ui/Badge';

describe('Badge', () => {
  it('renders children text', () => {
    render(<Badge>PAGE</Badge>);
    expect(screen.getByText('PAGE')).toBeInTheDocument();
  });

  it('defaults to info variant', () => {
    const { container } = render(<Badge>INFO</Badge>);
    const badge = container.querySelector('span');
    expect(badge?.className).toContain('info');
  });

  it('applies the specified variant class', () => {
    const { container } = render(<Badge variant="success">OK</Badge>);
    const badge = container.querySelector('span');
    expect(badge?.className).toContain('success');
  });

  it('applies error variant class', () => {
    const { container } = render(<Badge variant="error">FAIL</Badge>);
    const badge = container.querySelector('span');
    expect(badge?.className).toContain('error');
  });

  it('applies draft variant class', () => {
    const { container } = render(<Badge variant="draft">DRAFT</Badge>);
    const badge = container.querySelector('span');
    expect(badge?.className).toContain('draft');
  });
});
