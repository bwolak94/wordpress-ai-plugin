import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect } from 'vitest';
import { Input } from '../components/ui/Input';

describe('Input', () => {
  it('renders label and input', () => {
    render(<Input label="Username" />);

    expect(screen.getByLabelText('Username')).toBeInTheDocument();
    expect(screen.getByText('Username')).toBeInTheDocument();
  });

  it('generates id from label', () => {
    render(<Input label="First Name" />);

    const input = screen.getByLabelText('First Name');
    expect(input).toHaveAttribute('id', 'first-name');
  });

  it('uses provided id over generated one', () => {
    render(<Input label="Email" id="custom-email" />);

    const input = screen.getByLabelText('Email');
    expect(input).toHaveAttribute('id', 'custom-email');
  });

  it('shows error message', () => {
    render(<Input label="Email" error="Email is required" />);

    expect(screen.getByRole('alert')).toHaveTextContent('Email is required');
  });

  it('sets aria-invalid when error present', () => {
    render(<Input label="Email" error="Invalid email" />);

    const input = screen.getByLabelText('Email');
    expect(input).toHaveAttribute('aria-invalid', 'true');
  });

  it('does not set aria-invalid when no error', () => {
    render(<Input label="Email" />);

    const input = screen.getByLabelText('Email');
    expect(input).toHaveAttribute('aria-invalid', 'false');
  });

  it('sets aria-describedby pointing to error element', () => {
    render(<Input label="Password" error="Too short" />);

    const input = screen.getByLabelText('Password');
    expect(input).toHaveAttribute('aria-describedby', 'password-error');

    const errorEl = document.getElementById('password-error');
    expect(errorEl).toHaveTextContent('Too short');
  });

  it('passes through HTML attributes', async () => {
    const user = userEvent.setup();

    render(<Input label="Search" placeholder="Type here..." type="search" />);

    const input = screen.getByLabelText('Search');
    expect(input).toHaveAttribute('placeholder', 'Type here...');
    expect(input).toHaveAttribute('type', 'search');

    await user.type(input, 'hello');
    expect(input).toHaveValue('hello');
  });
});
