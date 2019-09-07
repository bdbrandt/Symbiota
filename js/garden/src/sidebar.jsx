"use strict";

const helpButtonStyle = {
  float: "right",
  padding: 0,
  marginLeft: "auto",
  borderRadius: "50%",
  background: "#5FB021",
};

const searchButtonStyle = {
  width: "2em",
  height: "2em",
  padding: "0.3em",
  marginLeft: "0.5em",
  borderRadius: "50%",
  background: "rgba(255, 255, 255, 0.5)"
};

function getSliderDescription(valueArray) {
  let valueDesc;

  // Fix if the handles have switched
  if (valueArray[0] > valueArray[1]) {
    let tmp = valueArray[0];
    valueArray[0] = valueArray[1];
    valueArray[1] = tmp;
  }

  if (valueArray[0] === 0 && valueArray[1] === 50) {
    valueDesc = "(Any size)";
  } else if (valueArray[0] === 0) {
    valueDesc = `(At most ${valueArray[1]} ft)`;
  } else if (valueArray[1] === 50) {
    valueDesc = `(At least ${valueArray[0]} ft)`;
  } else {
    valueDesc = `(${valueArray[0]} ft - ${valueArray[1]} ft)`
  }

  return valueDesc;
}

/**
 * Sidebar header with title, subtitle, and help
 */
function SideBarHeading(props) {
  return (
    <div style={{color: "black"}}>
      <div className="mb-1" style={{color: "inherit"}}>
        <h3 className="font-weight-bold d-inline">Search for plants</h3>
        <button style={ helpButtonStyle }>
          <img
            style={{ width: "1.25em" }}
            alt="help"
            src="/images/garden/help.png"/>
        </button>
      </div>
      <p>
        Start applying characteristics, and the matching plants will appear at
        right.
      </p>
    </div>
  );
}

/**
 * Sidebar 'plant search' button
 */
function SearchButton(props) {
  return (
    <button className="my-auto" style={ searchButtonStyle } onClick={ props.onClick }>
      <img
        style={{ display: props.showLoading ? "none" : "block" }}
        src="/images/garden/search-green.png"
        alt="search plants"/>
      <div
        className="mx-auto text-success spinner-border spinner-border-sm"
        style={{ display: props.showLoading ? "block" : "none" }}
        role="status"
        aria-hidden="true"/>
    </button>
  );
}

/**
 * Sidebar 'plant search' text field & button
 */
function SideBarSearch(props) {
  return (
    <div className="input-group w-100 mb-4 p-2">
      <input
        name="search"
        type="text"
        placeholder="Search plants by name"
        className="form-control"
        onChange={ props.onChange }
        value={ props.value } />
      <SearchButton onClick={ props.onClick } showLoading={ props.isLoading } />
    </div>
  );
}

/**
 * 'Plant Need' dropdown with label
 */
function PlantNeed(props) {
  return (
    <div className = "input-group pt-3 mt-3" style={{ borderTop: "1px dashed black" }}>
      <label className="font-weight-bold" htmlFor={ props.label.toLowerCase() }>
        { props.label }
      </label>
      <select
        id="sunlight"
        name={ props.label.toLowerCase() }
        className="form-control ml-auto"
        style={{ maxWidth: "50%" }}
        value={ props.value }
        onChange={ props.onChange }>
        <option value="" disabled hidden>Select...</option>
        {
          props.choices.map((opt) =>
            <option key={ opt.toLowerCase() } value={ opt.toLowerCase() }>
              { opt }
            </option>
          )
        }
      </select>
    </div>
  );
}

class PlantSlider extends React.Component {
  constructor(props) {
    super(props);
    this.state = { description: "(Any size)" }
  }

  componentDidMount() {
    const sliderId = `slider-container-${this.props.name}`;
    this.slider = new Slider(`#${sliderId}`);

    if (this.props.onChange) {
      const onChangeEvent = this.props.onChange;
      this.slider.on("slide", (sliderArray) => {
        this.setState({ description: getSliderDescription(sliderArray) });
        const fakeEvent = { target: { value: sliderArray } };
        onChangeEvent(fakeEvent);
      });
    }
  }

  componentWillUnmount() {
    this.slider.destroy();
  }

  render() {
    return (
      <div>
        <label className="d-block text-center" htmlFor={ this.props.name }>{ this.props.label }</label>
        <input
          id={ "slider-container-" + this.props.name }
          type="text"
          className="bootstrap-slider"
          name={ this.props.name }
          data-slider-value="[0, 50]"
          data-slider-ticks="[0, 10, 20, 30, 40, 50]"
          data-slider-ticks-labels='["0", "", "", "", "", "50+"]'
          data-slider-ticks-snap-bounds="1"
          onChange={ (e) => this.props.onChange(e) }
        />
        <br/>
        <label className="d-block text-center" htmlFor={ this.props.name }>
          { this.state.description }
        </label>
      </div>
    );
  }
}

/**
 * Full sidebar
 */
class SideBar extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      search: '',
      sunlight: '',
      moisture: '',
      width: [0, 50],
      height: [0, 50],
      widthDesc: "(Any size)",
      heightDesc: "(Any size)",
      isLoading: false
    };

    this.onSearchTextChanged =  this.onSearchTextChanged.bind(this);
    this.onSearch = this.onSearch.bind(this);
    this.onSunlightChanged = this.onSunlightChanged.bind(this);
    this.onMoistureChanged = this.onMoistureChanged.bind(this);
    this.onHeightChanged =  this.onHeightChanged.bind(this);
    this.onWidthChanged = this.onWidthChanged.bind(this);
  }

  onSearchTextChanged(event) {
    this.setState({ searchText: event.target.value });
  }

  onSearch() {
    this.setState({ isLoading: true });

    // TODO: Search!
    console.log("The current search value is '" + this.state.search + "'");
    setTimeout(() => { this.setState({ isLoading: false }) }, 3000);
  }

  onSunlightChanged(event) {
    console.log("The current sunlight value is '" + event.target.value + "'");
    this.setState({ sunlight: event.target.value });
  }

  onMoistureChanged(event) {
    console.log("The current moisture value is '" + event.target.value + "'");
    this.setState({ moisture: event.target.value });
  }

  onHeightChanged(event) {
    this.setState({ height: event.target.value });
    console.log("The current height value is " + this.state.height);
  }

  onWidthChanged(event) {
    this.setState({ width: event.target.value });
    console.log("The current width value is " + this.state.width);
  }

  render() {
    return (
      <div
        id="sidebar"
        className="col-sm-3 m-2 p-5 rounded-border"
        style={ this.props.style }>
        {/* Title & Subtitle */}
        <SideBarHeading />

        {/* Search */}
        <SideBarSearch
          onChange={ this.onSearchTextChanged }
          onClick={ this.onSearch }
          isLoading={ this.state.isLoading }
          value={ this.state.search }
        />

        {/* Sunlight & Moisture */}
        <div style={{ background: "white" }} className="rounded-border p-4">
          <h4>Plant needs</h4>
          <PlantNeed
            label="Sunlight"
            choices={ ["Sun", "Part-Shade", "Full-Shade"] }
            value={ this.state.sunlight }
            onChange={ this.onSunlightChanged } />
          <PlantNeed
            label="Moisture"
            choices={ ["Dry", "Moderate", "Wet"] }
            value={ this.state.moisture }
            onChange={ this.onMoistureChanged } />
        </div>

        {/* Sliders */}
        <div className="my-5">
          <h4 className="mr-2 mb-2 d-inline">Mature Size</h4>
          <span>(Just grab the slider dots)</span><br />
          <div className="mt-2 row d-flex justify-content-center">
            <div className="col-sm-5 mr-2">
              <PlantSlider
                label="Height (ft)"
                name="height"
                description={ this.state.heightDesc }
                onChange={ this.onHeightChanged } />
            </div>
            <div
              style={{ width: "1px", borderRight: "1px dashed grey", marginLeft: "-0.5px" }}
            />
            <div className="col-sm-5 ml-2">
              <PlantSlider
                label="Width (ft)"
                name="width"
                description={ this.state.widthDesc }
                onChange={ this.onWidthChanged } />
            </div>
          </div>
        </div>

      </div>
    );
  }
}

export default SideBar;
