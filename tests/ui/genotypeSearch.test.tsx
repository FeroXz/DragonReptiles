import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { GenotypeSearch } from '@components/genetics/GenotypeSearch.js';
import type { ParentGenotype } from '@lib/genetics/types.js';

type SpeciesKey = 'hognose' | 'pogona';

function renderGenotypeSearch(species: SpeciesKey = 'hognose') {
  const updates: ParentGenotype[] = [];
  const user = userEvent.setup();

  function Wrapper() {
    const [value, setValue] = useState<ParentGenotype>({});
    return (
      <GenotypeSearch
        species={species}
        value={value}
        onChange={(next) => {
          updates.push(next);
          setValue(next);
        }}
      />
    );
  }

  const view = render(<Wrapper />);
  return { user, updates, ...view };
}

describe('GenotypeSearch', () => {
  it('lists recessive matches for alb search', async () => {
    const { user } = renderGenotypeSearch();
    const input = screen.getByRole('textbox');

    await act(async () => {
      await user.type(input, 'alb');
    });

    const groupHeading = await screen.findByText('Rezessiv', { selector: '.genotype-search__group-label' });
    const group = groupHeading.closest('.genotype-search__group');
    expect(group).not.toBeNull();

    const options = within(group as HTMLElement).getAllByRole('option');
    expect(options).toHaveLength(2);
    expect(options[0]).toHaveTextContent('Albino');
    expect(options[1]).toHaveTextContent('het Albino');
  });

  it('switches recessive states between het and expressed', async () => {
    const { user, updates } = renderGenotypeSearch();
    const input = screen.getByRole('textbox');

    await act(async () => {
      await user.type(input, 'alb');
    });
    const hetOption = await screen.findByRole('option', { name: /het Albino/i });
    await act(async () => {
      await user.click(hetOption);
    });

    expect(updates[updates.length - 1].albino).toBe('het');

    await act(async () => {
      await user.type(input, 'alb');
    });
    const albinoOptions = await screen.findAllByRole('option', { name: /Albino/ });
    const expressedOption = albinoOptions.find((option) => option.textContent?.trim().startsWith('Albino'));
    expect(expressedOption).toBeDefined();
    await act(async () => {
      await user.click(expressedOption!);
    });

    expect(updates[updates.length - 1].albino).toBe('expressed');
    expect(screen.getByRole('button', { name: 'Albino entfernen' })).toBeInTheDocument();
  });

  it('promotes incomplete dominant genes to super forms', async () => {
    const { user, updates } = renderGenotypeSearch();
    const input = screen.getByRole('textbox');

    await act(async () => {
      await user.type(input, 'anac');
    });
    const anacondaOption = await screen.findByRole('option', { name: /^Anaconda/i });
    await act(async () => {
      await user.click(anacondaOption);
    });
    expect(updates[updates.length - 1].anaconda).toBe('expressed');

    await act(async () => {
      await user.type(input, 'super');
    });
    const superOption = await screen.findByRole('option', { name: /Superconda/i });
    await act(async () => {
      await user.click(superOption);
    });

    expect(updates[updates.length - 1].anaconda).toBe('super');
    expect(screen.getByRole('button', { name: 'Superconda entfernen' })).toBeInTheDocument();
  });

  it('sets dominant genes to expressed without super options', async () => {
    const { user, updates } = renderGenotypeSearch('pogona');
    const input = screen.getByRole('textbox');

    await act(async () => {
      await user.type(input, 'dun');
    });
    const options = await screen.findAllByRole('option', { name: /Dunner/i });
    expect(options).toHaveLength(1);

    await act(async () => {
      await user.click(options[0]);
    });
    expect(updates[updates.length - 1].dunner).toBe('expressed');
  });
});
