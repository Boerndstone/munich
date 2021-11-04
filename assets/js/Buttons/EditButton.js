import React, { Component } from 'react';

export default class EditButton extends Component {
    render() {
        
        return (
            <a 
                className="inline-flex items-center bg-teal-500 text-white active:bg-teal-600 font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" 
                role="button"
                href="{{ path('rock_edit', {'id': rock.id}) }}"
            >
                <svg className="w-4 h-4 mr-3 fill-current" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" clipRule="evenodd" fillRule="evenodd"></path></svg>
                <span>bearbeiten</span>
            </a>

        );
    }
}
