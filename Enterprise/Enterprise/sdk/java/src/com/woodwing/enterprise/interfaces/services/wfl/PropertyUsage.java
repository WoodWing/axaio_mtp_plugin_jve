/**
 * PropertyUsage.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PropertyUsage  implements java.io.Serializable {
    private java.lang.String name;

    private boolean editable;

    private boolean mandatory;

    private boolean restricted;

    private boolean refreshOnChange;

    private java.math.BigInteger initialHeight;

    private java.lang.Boolean multipleObjects;

    public PropertyUsage() {
    }

    public PropertyUsage(
           java.lang.String name,
           boolean editable,
           boolean mandatory,
           boolean restricted,
           boolean refreshOnChange,
           java.math.BigInteger initialHeight,
           java.lang.Boolean multipleObjects) {
           this.name = name;
           this.editable = editable;
           this.mandatory = mandatory;
           this.restricted = restricted;
           this.refreshOnChange = refreshOnChange;
           this.initialHeight = initialHeight;
           this.multipleObjects = multipleObjects;
    }


    /**
     * Gets the name value for this PropertyUsage.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this PropertyUsage.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the editable value for this PropertyUsage.
     * 
     * @return editable
     */
    public boolean isEditable() {
        return editable;
    }


    /**
     * Sets the editable value for this PropertyUsage.
     * 
     * @param editable
     */
    public void setEditable(boolean editable) {
        this.editable = editable;
    }


    /**
     * Gets the mandatory value for this PropertyUsage.
     * 
     * @return mandatory
     */
    public boolean isMandatory() {
        return mandatory;
    }


    /**
     * Sets the mandatory value for this PropertyUsage.
     * 
     * @param mandatory
     */
    public void setMandatory(boolean mandatory) {
        this.mandatory = mandatory;
    }


    /**
     * Gets the restricted value for this PropertyUsage.
     * 
     * @return restricted
     */
    public boolean isRestricted() {
        return restricted;
    }


    /**
     * Sets the restricted value for this PropertyUsage.
     * 
     * @param restricted
     */
    public void setRestricted(boolean restricted) {
        this.restricted = restricted;
    }


    /**
     * Gets the refreshOnChange value for this PropertyUsage.
     * 
     * @return refreshOnChange
     */
    public boolean isRefreshOnChange() {
        return refreshOnChange;
    }


    /**
     * Sets the refreshOnChange value for this PropertyUsage.
     * 
     * @param refreshOnChange
     */
    public void setRefreshOnChange(boolean refreshOnChange) {
        this.refreshOnChange = refreshOnChange;
    }


    /**
     * Gets the initialHeight value for this PropertyUsage.
     * 
     * @return initialHeight
     */
    public java.math.BigInteger getInitialHeight() {
        return initialHeight;
    }


    /**
     * Sets the initialHeight value for this PropertyUsage.
     * 
     * @param initialHeight
     */
    public void setInitialHeight(java.math.BigInteger initialHeight) {
        this.initialHeight = initialHeight;
    }


    /**
     * Gets the multipleObjects value for this PropertyUsage.
     * 
     * @return multipleObjects
     */
    public java.lang.Boolean getMultipleObjects() {
        return multipleObjects;
    }


    /**
     * Sets the multipleObjects value for this PropertyUsage.
     * 
     * @param multipleObjects
     */
    public void setMultipleObjects(java.lang.Boolean multipleObjects) {
        this.multipleObjects = multipleObjects;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PropertyUsage)) return false;
        PropertyUsage other = (PropertyUsage) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            this.editable == other.isEditable() &&
            this.mandatory == other.isMandatory() &&
            this.restricted == other.isRestricted() &&
            this.refreshOnChange == other.isRefreshOnChange() &&
            ((this.initialHeight==null && other.getInitialHeight()==null) || 
             (this.initialHeight!=null &&
              this.initialHeight.equals(other.getInitialHeight()))) &&
            ((this.multipleObjects==null && other.getMultipleObjects()==null) || 
             (this.multipleObjects!=null &&
              this.multipleObjects.equals(other.getMultipleObjects())));
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
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        _hashCode += (isEditable() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isMandatory() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isRestricted() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        _hashCode += (isRefreshOnChange() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getInitialHeight() != null) {
            _hashCode += getInitialHeight().hashCode();
        }
        if (getMultipleObjects() != null) {
            _hashCode += getMultipleObjects().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PropertyUsage.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyUsage"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editable");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editable"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("mandatory");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Mandatory"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("restricted");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Restricted"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("refreshOnChange");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RefreshOnChange"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("initialHeight");
        elemField.setXmlName(new javax.xml.namespace.QName("", "InitialHeight"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("multipleObjects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MultipleObjects"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
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
