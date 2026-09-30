export const renderSource = (source: string) => {
  const isExternalUrl = /^https?:\/\//i.test(source);

  return isExternalUrl ? (
    <a href={source} target="_blank" rel="noreferrer" title="Npcink">
      {source}
    </a>
  ) : (
    <span>{source}</span>
  );
};
