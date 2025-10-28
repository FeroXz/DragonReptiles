import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { ParentPicker } from '@components/genetics/ParentPicker.js';
import type { ParentGenotype } from '@lib/genetics/types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';
import translationsDe from '@i18n/genetics.de.json';

const translation = translationsDe as typeof translationsDe;

const baseMessages = {
  normal: translation.normal,
  het: translation.het,
  expressed: translation.expressed,
  super: translation.super,
  present: translation.present,
  posHet: translation.posHet,
  polygenicHint: translation.polygenicHint,
  warningIncompatible: translation.warningIncompatible,
  sectionTitles: translation.sectionTitles
};

type SpeciesKey = 'hognose' | 'pogona';

function getGenes(species: SpeciesKey) {
  return (species === 'hognose' ? hognoseGenes : pogonaGenes) as typeof hognoseGenes;
}

function renderParentPicker(species: SpeciesKey = 'hognose') {
  const updates: ParentGenotype[] = [];
  const user = userEvent.setup();

  function Wrapper() {
    const [value, setValue] = useState<ParentGenotype>({});
    return (
      <ParentPicker
        label="Test"
        genes={getGenes(species)}
        value={value}
        onChange={(next) => {
          updates.push(next);
          setValue(next);
        }}
        messages={baseMessages}
      />
    );
  }

  const view = render(<Wrapper />);
  return { user, updates, ...view };
}

describe('ParentPicker', () => {
  it('toggles recessive states and pos het slider', async () => {
    const { user, updates } = renderParentPicker();
    const albinoCard = await screen.findByText('Albino');
    const card = albinoCard.closest('.gene-card');
    expect(card).not.toBeNull();
    const hetButton = within(card as HTMLElement).getByRole('button', { name: translation.het });
    const expressedButton = within(card as HTMLElement).getByRole('button', { name: translation.expressed });

    await act(async () => {
      await user.click(hetButton);
    });
    expect(updates[updates.length - 1].albino).toBe('het');

    const posHetToggle = within(card as HTMLElement).getByRole('checkbox');
    await act(async () => {
      await user.click(posHetToggle);
    });
    const latest = updates[updates.length - 1].albino as { posHet: number };
    expect(typeof latest.posHet).toBe('number');

    await act(async () => {
      await user.click(expressedButton);
    });
    expect(updates[updates.length - 1].albino).toBe('expressed');
  });

  it('promotes incomplete dominant genes to super', async () => {
    const { user, updates } = renderParentPicker();
    const anacondaCard = await screen.findByText('Anaconda');
    const card = anacondaCard.closest('.gene-card');
    expect(card).not.toBeNull();

    const expressedButton = within(card as HTMLElement).getByRole('button', { name: translation.expressed });
    const superButton = within(card as HTMLElement).getByRole('button', { name: translation.super });

    await act(async () => {
      await user.click(expressedButton);
    });
    expect(updates[updates.length - 1].anaconda).toBe('expressed');

    await act(async () => {
      await user.click(superButton);
    });
    expect(updates[updates.length - 1].anaconda).toBe('super');
  });

  it('sets dominant genes to expressed', async () => {
    const { user, updates } = renderParentPicker('pogona');
    const dunnerCard = await screen.findByText('Dunner');
    const card = dunnerCard.closest('.gene-card');
    expect(card).not.toBeNull();

    const presentButton = within(card as HTMLElement).getByRole('button', { name: translation.present });

    await act(async () => {
      await user.click(presentButton);
    });
    expect(updates[updates.length - 1].dunner).toBe('expressed');
  });
});
