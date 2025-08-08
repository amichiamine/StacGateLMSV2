import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@/test/test-utils'
import { Input } from '../input'

describe('Input Component', () => {
  it('renders with placeholder text', () => {
    render(<Input placeholder="Enter text here" data-testid="input-test" />)
    const input = screen.getByTestId('input-test')
    expect(input).toBeInTheDocument()
    expect(input).toHaveAttribute('placeholder', 'Enter text here')
  })

  it('handles value changes', () => {
    const handleChange = vi.fn()
    render(<Input onChange={handleChange} data-testid="input-change" />)
    
    const input = screen.getByTestId('input-change')
    fireEvent.change(input, { target: { value: 'new value' } })
    
    expect(handleChange).toHaveBeenCalled()
  })

  it('applies custom className', () => {
    render(<Input className="custom-class" data-testid="input-class" />)
    const input = screen.getByTestId('input-class')
    expect(input).toHaveClass('custom-class')
  })

  it('can be disabled', () => {
    render(<Input disabled data-testid="input-disabled" />)
    const input = screen.getByTestId('input-disabled')
    expect(input).toBeDisabled()
  })

  it('supports different types', () => {
    render(<Input type="email" data-testid="input-email" />)
    const input = screen.getByTestId('input-email')
    expect(input).toHaveAttribute('type', 'email')
  })
})