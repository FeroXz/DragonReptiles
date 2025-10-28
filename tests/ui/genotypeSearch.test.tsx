import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { GenotypeSearch } from '@components/genetics/GenotypeSearch.js';
import type { ParentGenotype, SpeciesKey } from '@lib/genetics/types.js';

describe('GenotypeSearch', () => {
  function Wrapper({ species = 'hognose' as SpeciesKey }) {
    const [value, setValue] = useState<ParentGenotype>({});
    return <GenotypeSearch species={species} value={value} onChange={setValue} />;
  }

  afterEach(() => {
    jest.useRealTimers();
  });

  it('filters options by query and toggles het/expressed states', async () => {
    jest.useFakeTimers();
    const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
    render(<Wrapper />);

    const input = screen.getByRole('textbox');
    await act(async () => {
      await user.type(input, 'axan');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    const hetOption = await screen.findByRole('button', { name: 'Het Axanthic' });
    await act(async () => {
      await user.click(hetOption);
    });

    expect(screen.getByText('Het Axanthic')).toBeInTheDocument();

    await act(async () => {
      await user.type(input, 'axan');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    const expressedOption = await screen.findByRole('button', { name: 'Axanthic' });
    await act(async () => {
      await user.click(expressedOption);
    });

    expect(screen.queryByText('Het Axanthic')).not.toBeInTheDocument();
    expect(screen.getByText('Axanthic')).toBeInTheDocument();
  });

  it('promotes incomplete dominant genes to super labels', async () => {
    jest.useFakeTimers();
    const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
    render(<Wrapper />);

    const input = screen.getByRole('textbox');
    await act(async () => {
      await user.type(input, 'anac');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    const expressedOption = await screen.findByRole('button', { name: 'Anaconda' });
    await act(async () => {
      await user.click(expressedOption);
    });

    expect(screen.getByText('Anaconda')).toBeInTheDocument();

    await act(async () => {
      await user.type(input, 'super');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    const superOption = await screen.findByRole('button', { name: 'Superconda' });
    await act(async () => {
      await user.click(superOption);
    });

    expect(screen.queryByText('Anaconda')).not.toBeInTheDocument();
    expect(screen.getByText('Superconda')).toBeInTheDocument();
  });
});
