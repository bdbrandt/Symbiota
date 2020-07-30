import React from "react";

import HelpButton from "../common/helpButton.jsx";
import {SearchWidget} from "../common/search.jsx";
import ViewOpts from "./viewOpts.jsx";

/**
 * Full sidebar
 */
class SideBar extends React.Component {
  constructor(props) {
    super(props);
		this.onSearchNameClicked = this.props.onSearchNameClicked.bind(this);
		this.onSearchSynonymsClicked = this.props.onSearchSynonymsClicked.bind(this);
  }

  render() {

    return (
      <div
        id="sidebar"
        className="m-1 rounded-border"
        style={ this.props.style }>

				<div className="currently-displayed">
					<h3>Currently displayed:</h3>
					<div className="stat">
						<div className="stat-label">Families:</div><div className="stat-value">{ this.props.totals['families'] }</div>
					</div>
					<div className="stat">
						<div className="stat-label">Genera:</div><div className="stat-value">{ this.props.totals['genera'] }</div>
					</div>
					<div className="stat">
						<div className="stat-label">Species:</div><div className="stat-value">{ this.props.totals['species'] } (species rank)</div>
					</div>
					<div className="stat">
						<div className="stat-label">Total Taxa:</div><div className="stat-value">{ this.props.totals['taxa'] } (including subsp. and var.)</div>
					</div>
					<div className="stat export">
						<div className="stat-label">Export:</div><div className="stat-value">W CSV P</div>
					</div>
				</div>

					{
					<SearchWidget
						placeholder="Search this checklist"
						clientRoot={ this.props.clientRoot }
						isLoading={ this.props.isLoading }
						textValue={ this.props.searchText }
						onTextValueChanged={ this.props.onSearchTextChanged }
						onSearch={ this.props.onSearch }
						suggestionUrl={ this.props.searchSuggestionUrl }
						clid={ this.props.clid }
						searchName={ this.props.searchName }
					/>
					}
	
					<div id="view-opts-search" className="row mx-2 mt-3 px-0 py-2">

						<div className="col text-right p-0 mx-1 mt-auto">
							<p>Search:</p>
							<p>Include:</p>
						</div>
						<div className="col-auto p-0 mx-1 mt-auto">
							<p>
								<input 
									type="radio"
									name={ "searchname" }
									value={ "sciname" }
									onChange={() => {
										this.onSearchNameClicked("sciname")
									}}
									checked={this.props.searchName === 'sciname'? true: false}
									
								/> Scientific Names
							</p>
							<p>
								<input 
									type="radio"
									name={ "searchname" }
									value={ "commonname" }
									onChange={() => {
										this.onSearchNameClicked("commonname")
									}}
									checked={this.props.searchName === 'commonname'? true: false}
								/> Common Names
							</p>
							<p>
								<span>
									<input 
										type="checkbox" 
										name={ "searchSynonyms" } 
										value={ this.props.searchSynonyms == 'on' ? "on" : "off" } 
										onChange={() => {
											this.onSearchSynonymsClicked(this.props.searchSynonyms == 'on' ? "off" : "on" )
										}}
										checked={this.props.searchSynonyms === 'on'? true: false}
									/>
									<label className="ml-2 align-middle" htmlFor={ "searchSynonyms" }>{ "Synonyms" }</label>
								</span>
							</p>
						</div>
					</div>
					
					
					

				<ViewOpts
					viewType={ this.props.viewType }
					sortBy={ this.props.sortBy }
					showTaxaDetail={ this.props.showTaxaDetail }
					onSortByClicked={ this.onSortByChanged }
					onSearchNameClicked={ this.props.onViewTypeClicked }
					onSearchSynonymsClicked={ this.props.onSearchSynonymsClicked }
					onViewTypeClicked={ this.props.onViewTypeClicked }
					onTaxaDetailClicked={ this.props.onTaxaDetailClicked }
					//onFilterClicked={ this.onFilterRemoved }
					filters={
						Object.keys(this.props.filters).map((filterKey) => {
							return { key: filterKey, val: this.props.filters[filterKey] }
						})
					}
				/>
      </div>
    );
  }
}

SideBar.defaultProps = {
  searchText: '',
  searchSugestionUrl: '',
  //onPlantFeaturesChanged: () => {},
  //onGrowthMaintenanceChanged: () => {},
  //onBeyondGardenChanged: () => {}
};

export default SideBar;
