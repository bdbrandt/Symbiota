export function getTaxaPage(clientRoot, tid) {
  return `${clientRoot}/taxa/index.php?taxon=${tid}`;
}

export function getGardenTaxaPage(clientRoot, tid) {
  return `${clientRoot}/taxa/garden.php?taxon=${tid}`;
}

export function getImageDetailPage(clientRoot, imgid) {
  return `${clientRoot}/imagelib/imgdetails.php?imgid=${imgid}`;
}

export function getCommonNameStr(item) {
  const basename = item.vernacular.basename;
  const names = item.vernacular.names;

  let cname = basename;
  if (names.length > 0) {
    cname = names[0];
  }

  if (cname.includes(basename) && basename !== cname) {
    return `${basename}, ${cname.replace(basename, '')}`
  }

  return cname;
}

export function getChecklistPage(clientRoot,clid,pid) {
  return `${clientRoot}/checklists/checklist.php?cl=${clid}&pid=${pid}`;
}

export function getIdentifyPage(clientRoot,clid,pid) {
  return `${clientRoot}/ident/key.php?cl=${clid}&proj=${pid}&taxon=All+Species`;
}