export function buildAdminDataTableTemplate(options, dom) {
  const scrollStyle = options.scrollY.length
    ? ` style="height: ${options.scrollY}; overflow-y: auto;"`
    : '';

  const perPageBlock =
    options.paging && options.perPageSelect
      ? `<div class="${options.classes.dropdown} admin-datatable-toolbar__per-page">
            <label class="admin-datatable-toolbar__label">
              <span class="admin-datatable-toolbar__label-text">${options.labels.perPage}</span>
              <select class="${options.classes.selector}"></select>
            </label>
          </div>`
      : '';

  const searchBlock = options.searchable
    ? `<div class="${options.classes.search} admin-datatable-toolbar__search">
          <input class="${options.classes.input}" placeholder="${options.labels.placeholder}" type="search" title="${options.labels.searchTitle}"${dom.id ? ` aria-controls="${dom.id}"` : ''}>
        </div>`
    : '';

  return `<div class="${options.classes.top} admin-datatable-toolbar">
      ${perPageBlock}
      ${searchBlock}
    </div>
    <div class="${options.classes.container}"${scrollStyle}></div>
    <div class="${options.classes.bottom} admin-datatable-footer">
      <div class="${options.classes.info}"></div>
      <nav class="${options.classes.pagination}"></nav>
    </div>`;
}

export function createAdminDataTableOptions(overrides = {}) {
  const { labels: labelOverrides, ...rest } = overrides;

  return {
    searchable: true,
    sortable: true,
    perPage: 15,
    perPageSelect: [10, 15, 25, 50],
    template: buildAdminDataTableTemplate,
    ...rest,
    labels: {
      perPage: 'Lignes par page',
      placeholder: 'Rechercher…',
      noRows: 'Aucun résultat',
      info: '{start}–{end} sur {rows}',
      ...labelOverrides,
    },
  };
}
