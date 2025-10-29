import { act, render, screen, within } from '@testing-library/react';
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

    const input = screen.getByRole('combobox', { name: 'Traits durchsuchen' });
    await act(async () => {
      await user.type(input, 'axan');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });


    const listbox = await screen.findByRole('listbox');
    const hetOption = await within(listbox).findByRole('option', { name: 'Het Axanthic' });
    await act(async () => {
      await user.click(hetOption);
    });

    expect(screen.getByRole('button', { name: 'Trait Het Axanthic entfernen' })).toBeInTheDocument();

    await act(async () => {
      await user.type(input, 'axan');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });


    const listboxAfterHet = await screen.findByRole('listbox');
    const expressedOption = await within(listboxAfterHet).findByRole('option', { name: 'Axanthic' });
    await act(async () => {
      await user.click(expressedOption);
    });

    expect(screen.queryByRole('button', { name: 'Trait Het Axanthic entfernen' })).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Trait Axanthic entfernen' })).toBeInTheDocument();
  });

  it('promotes incomplete dominant genes to super labels', async () => {
    jest.useFakeTimers();
    const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
    render(<Wrapper />);

    const input = screen.getByRole('combobox', { name: 'Traits durchsuchen' });
    await act(async () => {
      await user.type(input, 'anac');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    const listbox = await screen.findByRole('listbox');
    const expressedOption = await within(listbox).findByRole('option', { name: 'Anaconda' });
    await act(async () => {
      await user.click(expressedOption);
    });

    expect(screen.getByRole('button', { name: 'Trait Anaconda entfernen' })).toBeInTheDocument();

    await act(async () => {
      await user.type(input, 'super');
    });
    act(() => {
      jest.advanceTimersByTime(200);
    });

    const listboxAfterExpressed = await screen.findByRole('listbox');
    const superOption = await within(listboxAfterExpressed).findByRole('option', { name: 'Superconda' });
    await act(async () => {
      await user.click(superOption);
    });

    expect(screen.queryByRole('button', { name: 'Trait Anaconda entfernen' })).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Trait Superconda entfernen' })).toBeInTheDocument();
  });
});
