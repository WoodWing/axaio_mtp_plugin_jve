/**
 * Dialog.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Dialog  implements java.io.Serializable {
    private java.lang.String title;

    private com.woodwing.enterprise.interfaces.services.wfl.DialogTab[] tabs;

    private com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData;

    private com.woodwing.enterprise.interfaces.services.wfl.DialogButton[] buttonBar;

    public Dialog() {
    }

    public Dialog(
           java.lang.String title,
           com.woodwing.enterprise.interfaces.services.wfl.DialogTab[] tabs,
           com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData,
           com.woodwing.enterprise.interfaces.services.wfl.DialogButton[] buttonBar) {
           this.title = title;
           this.tabs = tabs;
           this.metaData = metaData;
           this.buttonBar = buttonBar;
    }


    /**
     * Gets the title value for this Dialog.
     * 
     * @return title
     */
    public java.lang.String getTitle() {
        return title;
    }


    /**
     * Sets the title value for this Dialog.
     * 
     * @param title
     */
    public void setTitle(java.lang.String title) {
        this.title = title;
    }


    /**
     * Gets the tabs value for this Dialog.
     * 
     * @return tabs
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DialogTab[] getTabs() {
        return tabs;
    }


    /**
     * Sets the tabs value for this Dialog.
     * 
     * @param tabs
     */
    public void setTabs(com.woodwing.enterprise.interfaces.services.wfl.DialogTab[] tabs) {
        this.tabs = tabs;
    }


    /**
     * Gets the metaData value for this Dialog.
     * 
     * @return metaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] getMetaData() {
        return metaData;
    }


    /**
     * Sets the metaData value for this Dialog.
     * 
     * @param metaData
     */
    public void setMetaData(com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[] metaData) {
        this.metaData = metaData;
    }


    /**
     * Gets the buttonBar value for this Dialog.
     * 
     * @return buttonBar
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DialogButton[] getButtonBar() {
        return buttonBar;
    }


    /**
     * Sets the buttonBar value for this Dialog.
     * 
     * @param buttonBar
     */
    public void setButtonBar(com.woodwing.enterprise.interfaces.services.wfl.DialogButton[] buttonBar) {
        this.buttonBar = buttonBar;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Dialog)) return false;
        Dialog other = (Dialog) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.title==null && other.getTitle()==null) || 
             (this.title!=null &&
              this.title.equals(other.getTitle()))) &&
            ((this.tabs==null && other.getTabs()==null) || 
             (this.tabs!=null &&
              java.util.Arrays.equals(this.tabs, other.getTabs()))) &&
            ((this.metaData==null && other.getMetaData()==null) || 
             (this.metaData!=null &&
              java.util.Arrays.equals(this.metaData, other.getMetaData()))) &&
            ((this.buttonBar==null && other.getButtonBar()==null) || 
             (this.buttonBar!=null &&
              java.util.Arrays.equals(this.buttonBar, other.getButtonBar())));
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
        if (getTitle() != null) {
            _hashCode += getTitle().hashCode();
        }
        if (getTabs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTabs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTabs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMetaData() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMetaData());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMetaData(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getButtonBar() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getButtonBar());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getButtonBar(), i);
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
        new org.apache.axis.description.TypeDesc(Dialog.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Dialog"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("title");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Title"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("tabs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Tabs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "DialogTab"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "DialogTab"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("metaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaDataValue"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "MetaDataValue"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("buttonBar");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ButtonBar"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "DialogButton"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "DialogButton"));
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
