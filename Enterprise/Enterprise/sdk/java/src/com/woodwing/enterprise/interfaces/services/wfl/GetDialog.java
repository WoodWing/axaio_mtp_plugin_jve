/**
 * GetDialog.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetDialog  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String ID;

    private java.lang.String publication;

    private java.lang.String issue;

    private java.lang.String section;

    private java.lang.String state;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectType type;

    private com.woodwing.enterprise.interfaces.services.wfl.Action action;

    private boolean requestDialog;

    private boolean requestPublication;

    private boolean requestMetaData;

    private boolean requestStates;

    private boolean requestTargets;

    private java.lang.String defaultDossier;

    private java.lang.String parent;

    private java.lang.String template;

    private com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas;

    public GetDialog() {
    }

    public GetDialog(
           java.lang.String ticket,
           java.lang.String ID,
           java.lang.String publication,
           java.lang.String issue,
           java.lang.String section,
           java.lang.String state,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectType type,
           com.woodwing.enterprise.interfaces.services.wfl.Action action,
           boolean requestDialog,
           boolean requestPublication,
           boolean requestMetaData,
           boolean requestStates,
           boolean requestTargets,
           java.lang.String defaultDossier,
           java.lang.String parent,
           java.lang.String template,
           com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
           this.ticket = ticket;
           this.ID = ID;
           this.publication = publication;
           this.issue = issue;
           this.section = section;
           this.state = state;
           this.type = type;
           this.action = action;
           this.requestDialog = requestDialog;
           this.requestPublication = requestPublication;
           this.requestMetaData = requestMetaData;
           this.requestStates = requestStates;
           this.requestTargets = requestTargets;
           this.defaultDossier = defaultDossier;
           this.parent = parent;
           this.template = template;
           this.areas = areas;
    }


    /**
     * Gets the ticket value for this GetDialog.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this GetDialog.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the ID value for this GetDialog.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this GetDialog.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the publication value for this GetDialog.
     * 
     * @return publication
     */
    public java.lang.String getPublication() {
        return publication;
    }


    /**
     * Sets the publication value for this GetDialog.
     * 
     * @param publication
     */
    public void setPublication(java.lang.String publication) {
        this.publication = publication;
    }


    /**
     * Gets the issue value for this GetDialog.
     * 
     * @return issue
     */
    public java.lang.String getIssue() {
        return issue;
    }


    /**
     * Sets the issue value for this GetDialog.
     * 
     * @param issue
     */
    public void setIssue(java.lang.String issue) {
        this.issue = issue;
    }


    /**
     * Gets the section value for this GetDialog.
     * 
     * @return section
     */
    public java.lang.String getSection() {
        return section;
    }


    /**
     * Sets the section value for this GetDialog.
     * 
     * @param section
     */
    public void setSection(java.lang.String section) {
        this.section = section;
    }


    /**
     * Gets the state value for this GetDialog.
     * 
     * @return state
     */
    public java.lang.String getState() {
        return state;
    }


    /**
     * Sets the state value for this GetDialog.
     * 
     * @param state
     */
    public void setState(java.lang.String state) {
        this.state = state;
    }


    /**
     * Gets the type value for this GetDialog.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectType getType() {
        return type;
    }


    /**
     * Sets the type value for this GetDialog.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.wfl.ObjectType type) {
        this.type = type;
    }


    /**
     * Gets the action value for this GetDialog.
     * 
     * @return action
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Action getAction() {
        return action;
    }


    /**
     * Sets the action value for this GetDialog.
     * 
     * @param action
     */
    public void setAction(com.woodwing.enterprise.interfaces.services.wfl.Action action) {
        this.action = action;
    }


    /**
     * Gets the requestDialog value for this GetDialog.
     * 
     * @return requestDialog
     */
    public boolean isRequestDialog() {
        return requestDialog;
    }


    /**
     * Sets the requestDialog value for this GetDialog.
     * 
     * @param requestDialog
     */
    public void setRequestDialog(boolean requestDialog) {
        this.requestDialog = requestDialog;
    }


    /**
     * Gets the requestPublication value for this GetDialog.
     * 
     * @return requestPublication
     */
    public boolean isRequestPublication() {
        return requestPublication;
    }


    /**
     * Sets the requestPublication value for this GetDialog.
     * 
     * @param requestPublication
     */
    public void setRequestPublication(boolean requestPublication) {
        this.requestPublication = requestPublication;
    }


    /**
     * Gets the requestMetaData value for this GetDialog.
     * 
     * @return requestMetaData
     */
    public boolean isRequestMetaData() {
        return requestMetaData;
    }


    /**
     * Sets the requestMetaData value for this GetDialog.
     * 
     * @param requestMetaData
     */
    public void setRequestMetaData(boolean requestMetaData) {
        this.requestMetaData = requestMetaData;
    }


    /**
     * Gets the requestStates value for this GetDialog.
     * 
     * @return requestStates
     */
    public boolean isRequestStates() {
        return requestStates;
    }


    /**
     * Sets the requestStates value for this GetDialog.
     * 
     * @param requestStates
     */
    public void setRequestStates(boolean requestStates) {
        this.requestStates = requestStates;
    }


    /**
     * Gets the requestTargets value for this GetDialog.
     * 
     * @return requestTargets
     */
    public boolean isRequestTargets() {
        return requestTargets;
    }


    /**
     * Sets the requestTargets value for this GetDialog.
     * 
     * @param requestTargets
     */
    public void setRequestTargets(boolean requestTargets) {
        this.requestTargets = requestTargets;
    }


    /**
     * Gets the defaultDossier value for this GetDialog.
     * 
     * @return defaultDossier
     */
    public java.lang.String getDefaultDossier() {
        return defaultDossier;
    }


    /**
     * Sets the defaultDossier value for this GetDialog.
     * 
     * @param defaultDossier
     */
    public void setDefaultDossier(java.lang.String defaultDossier) {
        this.defaultDossier = defaultDossier;
    }


    /**
     * Gets the parent value for this GetDialog.
     * 
     * @return parent
     */
    public java.lang.String getParent() {
        return parent;
    }


    /**
     * Sets the parent value for this GetDialog.
     * 
     * @param parent
     */
    public void setParent(java.lang.String parent) {
        this.parent = parent;
    }


    /**
     * Gets the template value for this GetDialog.
     * 
     * @return template
     */
    public java.lang.String getTemplate() {
        return template;
    }


    /**
     * Sets the template value for this GetDialog.
     * 
     * @param template
     */
    public void setTemplate(java.lang.String template) {
        this.template = template;
    }


    /**
     * Gets the areas value for this GetDialog.
     * 
     * @return areas
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AreaType[] getAreas() {
        return areas;
    }


    /**
     * Sets the areas value for this GetDialog.
     * 
     * @param areas
     */
    public void setAreas(com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
        this.areas = areas;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDialog)) return false;
        GetDialog other = (GetDialog) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.publication==null && other.getPublication()==null) || 
             (this.publication!=null &&
              this.publication.equals(other.getPublication()))) &&
            ((this.issue==null && other.getIssue()==null) || 
             (this.issue!=null &&
              this.issue.equals(other.getIssue()))) &&
            ((this.section==null && other.getSection()==null) || 
             (this.section!=null &&
              this.section.equals(other.getSection()))) &&
            ((this.state==null && other.getState()==null) || 
             (this.state!=null &&
              this.state.equals(other.getState()))) &&
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.action==null && other.getAction()==null) || 
             (this.action!=null &&
              this.action.equals(other.getAction()))) &&
            this.requestDialog == other.isRequestDialog() &&
            this.requestPublication == other.isRequestPublication() &&
            this.requestMetaData == other.isRequestMetaData() &&
            this.requestStates == other.isRequestStates() &&
            this.requestTargets == other.isRequestTargets() &&
            ((this.defaultDossier==null && other.getDefaultDossier()==null) || 
             (this.defaultDossier!=null &&
              this.defaultDossier.equals(other.getDefaultDossier()))) &&
            ((this.parent==null && other.getParent()==null) || 
             (this.parent!=null &&
              this.parent.equals(other.getParent()))) &&
            ((this.template==null && other.getTemplate()==null) || 
             (this.template!=null &&
              this.template.equals(other.getTemplate()))) &&
            ((this.areas==null && other.getAreas()==null) || 
             (this.areas!=null &&
              java.util.Arrays.equals(this.areas, other.getAreas())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getPublication() != null) {
            _hashCode += getPublication().hashCode();
        }
        if (getIssue() != null) {
            _hashCode += getIssue().hashCode();
        }
        if (getSection() != null) {
            _hashCode += getSection().hashCode();
        }
        if (getState() != null) {
            _hashCode += getState().hashCode();
        }
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getAction() != null) {
            _hashCode += getAction().hashCode();
        }
        _hashCode += (isRequestDialog() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isRequestPublication() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isRequestMetaData() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isRequestStates() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isRequestTargets() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getDefaultDossier() != null) {
            _hashCode += getDefaultDossier().hashCode();
        }
        if (getParent() != null) {
            _hashCode += getParent().hashCode();
        }
        if (getTemplate() != null) {
            _hashCode += getTemplate().hashCode();
        }
        if (getAreas() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAreas());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAreas(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetDialog.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publication");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publication"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Issue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("section");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Section"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("state");
        elemField.setXmlName(new javax.xml.namespace.QName("", "State"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("action");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Action"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Action"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestDialog");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestDialog"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestPublication");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestPublication"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestStates");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestStates"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestTargets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestTargets"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("defaultDossier");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DefaultDossier"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("parent");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Parent"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("template");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Template"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("areas");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Areas"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AreaType"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AreaType"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
