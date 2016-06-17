/**
 * Action.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Action implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected Action(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _value1 = "";
    public static final java.lang.String _value2 = "Create";
    public static final java.lang.String _value3 = "CheckIn";
    public static final java.lang.String _value4 = "SendTo";
    public static final java.lang.String _value5 = "CopyTo";
    public static final java.lang.String _value6 = "SetProperties";
    public static final java.lang.String _value7 = "Query";
    public static final java.lang.String _value8 = "Preview";
    public static final java.lang.String _value9 = "PublishDossier";
    public static final java.lang.String _value10 = "UpdateDossier";
    public static final java.lang.String _value11 = "UnPublishDossier";
    public static final java.lang.String _value12 = "SetPublishProperties";
    public static final Action value1 = new Action(_value1);
    public static final Action value2 = new Action(_value2);
    public static final Action value3 = new Action(_value3);
    public static final Action value4 = new Action(_value4);
    public static final Action value5 = new Action(_value5);
    public static final Action value6 = new Action(_value6);
    public static final Action value7 = new Action(_value7);
    public static final Action value8 = new Action(_value8);
    public static final Action value9 = new Action(_value9);
    public static final Action value10 = new Action(_value10);
    public static final Action value11 = new Action(_value11);
    public static final Action value12 = new Action(_value12);
    public java.lang.String getValue() { return _value_;}
    public static Action fromValue(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        Action enumeration = (Action)
            _table_.get(value);
        if (enumeration==null) throw new java.lang.IllegalArgumentException();
        return enumeration;
    }
    public static Action fromString(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        return fromValue(value);
    }
    public boolean equals(java.lang.Object obj) {return (obj == this);}
    public int hashCode() { return toString().hashCode();}
    public java.lang.String toString() { return _value_;}
    public java.lang.Object readResolve() throws java.io.ObjectStreamException { return fromValue(_value_);}
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumSerializer(
            _javaType, _xmlType);
    }
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumDeserializer(
            _javaType, _xmlType);
    }
    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Action.class);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Action"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
