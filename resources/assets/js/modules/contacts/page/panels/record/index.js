import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { getContact, getCustomFieldsForContacts } from '../../../store/selectors';
import { fetchContact } from '../../../service';
import _ from 'lodash';
import * as MDIcons from 'react-icons/lib/md'

class Record extends React.Component {
  constructor(props) {
    super(props)
  }

  componentWillMount() {
    if (this.props.match.params.id) {
      this.props.dispatch(fetchContact(this.props.match.params.id))
    }
  }

  render() {
    const { contact } = this.props;
    const groups = _.groupBy(this.props.customFields, 'group');
    const contactFields = Object.keys(groups).map(key => (
      <li key={key} className="list-group-item">
        <div className="mini-text text-muted">{key}</div>
        {groups[key].map(f => {
          let fieldValue = _.get(this.props.contact, f.alias);

          if (typeof fieldValue === 'object') {
            fieldValue = _.get(fieldValue, 'name');
          }

          const hidden = typeof fieldValue === 'undefined' || fieldValue.length === 0 ? 'd-none' : '';

          return (
            <div className={`form-group row ${hidden}`} key={f.fieldId}>
              <label htmlFor={f.alias} className="col-sm-3 col-form-label">{f.label}</label>
              <div className="col-sm-9">
                <input type="text" readOnly className="form-control-plaintext" id={f.alias} name={f.alias} value={fieldValue} />
              </div>
            </div>
          )
        })
      }
      </li>
    ));

    return (
      <main key={0} className="main-panel col-sm-3 col-md-5 offset-md-5">
          <div className="toolbar border-bottom py-2 heading">
            <button type="button" className="btn btn-default mr-2">1</button>
            <button type="button" className="btn btn-default mr-2">2</button>
            <button type="button" className="btn btn-default mr-2">3</button>
            <button type="button" className="btn btn-default mr-2">4</button>
            <button type="button" className="btn btn-default mr-2">5</button>
            <div className="float-right text-right">
              <div className="mini-text text-muted">Assigned To</div>
              <div className="text-dark mini-text"><b>{contact.user.name}</b></div>
            </div>
          </div>
          <h3 className="border-bottom py-1">{contact.firstName} {contact.lastName}</h3>
        <div className="card h-scroll">
          <ul className="list-group list-group-flush">
              {contactFields}
          </ul>
        </div>
      </main>
    )
  }
}

Record.propTypes = {
  contact: PropTypes.object.isRequired
}

export default withRouter(connect((state, ownProps) => ({
  contact: getContact(state, ownProps.match.params.id),
  customFields: getCustomFieldsForContacts(state)
}))(Record))