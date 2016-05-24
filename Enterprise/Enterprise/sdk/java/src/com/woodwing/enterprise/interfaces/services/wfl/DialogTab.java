/**
 * DialogTab.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class DialogTab  implements java.io.Serializable {
    private java.lang.String title;

    private com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] widgets;

    private java.lang.String defaultFocus;

    public DialogTab() {
    }

    public DialogTab(
           java.lang.String title,
           com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] widgets,
           java.lang.String defaultFocus) {
           this.title = title;
           this.widgets = widgets;
           this.defaultFocus = defaultFocus;
    }


    /**
     * Gets the title value for this DialogTab.
     * 
     * @return title
     */
    public java.lang.String getTitle() {
        return title;
    }


    /**
     * Sets the title value for this DialogTab.
     * 
     * @param title
     */
    public void setTitle(java.lang.String title) {
        this.title = title;
    }


    /**
     * Gets the widgets value for this DialogTab.
     * 
     * @return widgets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] getWidgets() {
        return widgets;
    }


    /**
     * Sets the widgets value for this DialogTab.
     * 
     * @param widgets
     */
    public void setWidgets(com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] widgets) {
        this.widgets = widgets;
    }


    /**
     * Gets the defaultFocus value for this DialogTab.
     * 
     * @return defaultFocus
     */
    public java.lang.String getDefaultFocus() {
        return defaultFocus;
    }


    /**
     * Sets the defaultFocus value for this DialogTab.
     * 
     * @param defaultFocus
     */
    public void setDefaultFocus(java.lang.String defaultFocus) {
        this.defaultFocus = defaultFocus;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof DialogTab)) return false;
        DialogTab other = (DialogTab) obj;
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
            ((this.widgets==null && other.getWidgets()==null) || 
             (this.widgets!=null &&
              java.util.Arrays.equals(this.widgets, other.getWidgets()))) &&
            ((this.defaultFocus==null && other.getDefaultFocus()==null) || 
             (this.defaultFocus!=null &&
              this.defaultFocus.equals(other.getDefaultFocus())));
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
        if (getWidgets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWidgets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWidgets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getDefaultFocus() != null) {
            _hashCode += getDefaultFocus().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(DialogTab.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "DialogTab"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("title");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Title"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("widgets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Widgets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "DialogWidget"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "DialogWidget"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("defaultFocus");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DefaultFocus"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
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
